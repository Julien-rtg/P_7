<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use App\Service\VersioningService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;   
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{
    protected $customerRepository;
    protected $userRepository;
    protected $serializer;
    protected $entityManager;
    protected $cachePool;

    public function __construct(
        CustomerRepository $customerRepository,
        UserRepository $userRepository,
        SerializerInterface $serializerInterface,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cachePool
    ) {
        $this->customerRepository = $customerRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializerInterface;
        $this->entityManager = $entityManager;
        $this->cachePool = $cachePool;
        
    }

    /** 
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste d'un utilisateur d'un client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getAllUsers"}))
     *     )
     * )
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'éléments que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Tag(name="Users")
     */
    #[Route('/api/customer/{id}/users', name: 'AllUsersFromCustomer', methods:['GET'])]
    public function getAllUsersFromCustomer(int $id, UserRepository $userRepository, Request $request, VersioningService $versioningService): JsonResponse
    {
        $customer = $this->customerRepository->find($id);
        
        if($customer){ 
            $customerConnectedId = $this->getUser()->getId();
            if ($customerConnectedId  === $id) {

                $page = $request->get('page', 1);
                $limit = $request->get('limit', 3);

                $idCache = "getAllUsersFromCustomer-". $id . "-" .  $page . "-" . $limit;
                $allUsersCustomer = $this->cachePool->get($idCache, function (ItemInterface $item) use ($userRepository, $id, $page, $limit) {
                    $item->tag("getAllUsersFromCustomer");
                    return $userRepository->findAllWithPagination($id, $page, $limit);
                });

                $version = $versioningService->getVersion();
                $context = SerializationContext::create()->setGroups(["getAllUsers"]);
                $context->setVersion($version);
                $jsonList = $this->serializer->serialize($allUsersCustomer, 'json', $context);

                return new JsonResponse(
                    $jsonList,
                    Response::HTTP_OK,
                    [],
                    true
                );
            }else{
                return new JsonResponse(
                    ['message' => 'Access denied'],
                    Response::HTTP_NOT_FOUND
                );
            }

        }else {

            return new JsonResponse(
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        }

    }

    /** 
     * @OA\Response(
     *     response=200,
     *     description="Retourne un utilisateur d'un client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getCustomerUsers"}))
     *     )
     * )
     * @OA\Tag(name="Users")
     */
    #[Route('/api/customer/{id}/user/{userId}', name: 'UserFromCustomer', methods:['GET'])]
    public function getUserFromCustomer(int $id, int $userId, UserRepository $userRepository): JsonResponse
    {
        $customer = $this->customerRepository->find($id);
        $user = $this->userRepository->find($userId);

        if($customer){
            $customerConnectedId = $this->getUser()->getId();
            if ($customerConnectedId  === $id) {
                $userCustomer = $this->userRepository->find($userId);
                if ($userCustomer === null) {
                    return new JsonResponse(['message' => 'This user does not exist'], Response::HTTP_NOT_FOUND);
                }
                
                if ($customerConnectedId === $user->getIdCustomer()->getId()) {
                    $idCache = "getUserFromCustomer-" . $id . "-" . $userId;
                    $userCustomer = $this->cachePool->get($idCache, function (ItemInterface $item) use ($userRepository, $userId) {
                        $item->tag("getUserFromCustomer");
                        return $userRepository->find($userId);
                    });
                    $context = SerializationContext::create()->setGroups(["getCustomerUsers"]);
                    $jsonList = $this->serializer->serialize($userCustomer, 'json', $context);

                    return new JsonResponse(
                        $jsonList,
                        Response::HTTP_OK,
                        [],
                        true
                    );
                } else {
                    return new JsonResponse(['message' => 'Access denied for this user'], Response::HTTP_FORBIDDEN);
                }
                
            } else {
                return new JsonResponse(
                    ['message' => 'Access denied'],
                    Response::HTTP_NOT_FOUND
                );
            }
            
        }else {
            return new JsonResponse(
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        }

    }

    /** 
     * @OA\Response(
     *     response=201,
     *     description="Ajoute un utilisateur pour un client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"addUser"}))
     *     )
     * )
     * @OA\Tag(name="Users")
     */
    #[Route('/api/customer/{id}/user', name: 'addUserFromCustomer', methods:['POST'])]
    public function addUserFromCustomer(Request $request, int $id, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if($customer){
            $customerConnectedId = $this->getUser()->getId();
            if ($customerConnectedId  === $id) {
                $contextDeserialization = DeserializationContext::create()->setGroups(["addUser"]);
                $newUser = $this->serializer->deserialize($request->getContent(), User::class, 'json', $contextDeserialization);

                if (
                    ($newUser->getFirstName() === null || $newUser->getFirstName() === "") &&
                    ($newUser->getLastName() === null || $newUser->getLastName() === "") &&
                    ($newUser->getEmail() === null || $newUser->getEmail() === "")
                ) {
                    return new JsonResponse(['message' => 'Fields must not be null or empty'], Response::HTTP_NOT_FOUND);
                } else if ($newUser->getFirstName() === null || $newUser->getFirstName() === "") {
                    return new JsonResponse(['message' => 'The field first_name must not be null or empty'], Response::HTTP_NOT_FOUND);
                } else if ($newUser->getLastName() === null || $newUser->getLastName() === "") {
                    return new JsonResponse(['message' => 'The field last_name must not be null or empty'], Response::HTTP_NOT_FOUND);
                } else if ($newUser->getEmail() === null || $newUser->getEmail() === "") {
                    return new JsonResponse(['message' => 'The field email must not be null or empty'], Response::HTTP_NOT_FOUND);
                } else {

                    $newUser->setIdCustomer($customer);

                    $this->entityManager->persist($newUser);
                    $this->entityManager->flush();

                    $context = SerializationContext::create()->setGroups(["addUser"]);
                    $jsonNewUser = $this->serializer->serialize($newUser, 'json', $context);
                    $location = $urlGenerator->generate('UserFromCustomer', ['id' => $customer->getId(), 'userId' =>  $newUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

                    return new JsonResponse(
                        $jsonNewUser,
                        Response::HTTP_CREATED,
                        ["Location" => $location],
                        true
                    );
                }

            } else {
                return new JsonResponse(
                    ['message' => 'Access denied'],
                    Response::HTTP_NOT_FOUND
                );
            }
            
        }else {

            return new JsonResponse(
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        }

    }

    /** 
     * 
     * @OA\Response(
     *     response=204,
     *     description="Suppression utilisateur",
     * )
     * @OA\Tag(name="Users")
     */
    #[Route('/api/customer/{id}/user/{userId}', name: 'deleteUserFromCustomer', methods:['DELETE'])]
    public function deleteUserFromCustomer(int $id, int $userId): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if($customer){
            $customerConnectedId = $this->getUser()->getId();
            if ($customerConnectedId  === $id) {
                $userToDelete = $this->userRepository->find($userId);

                $this->entityManager->remove($userToDelete);
                $this->entityManager->flush();

                return new JsonResponse(
                    ['message' => 'User successfully deleted'],
                    Response::HTTP_NO_CONTENT,
                );
            } else {
                return new JsonResponse(
                    ['message' => 'Access denied'],
                    Response::HTTP_NOT_FOUND
                );
            }

        }else {

            return new JsonResponse(
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        }

    }



    
}
