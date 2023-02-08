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

class UserController extends AbstractController
{
    protected $customerRepository;
    protected $userRepository;
    protected $serializer;
    protected $entityManager;

    public function __construct(
        CustomerRepository $customerRepository,
        UserRepository $userRepository,
        SerializerInterface $serializerInterface,
        EntityManagerInterface $entityManager,
    ) {
        $this->customerRepository = $customerRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializerInterface;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/customer/{id}/users', name: 'AllUsersFromCustomer', methods:['GET'])]
    public function getAllUsersFromCustomer(int $id, VersioningService $versioningService): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if($customer){

            // je dois faire une vérification si l'id du customer connecté == a l'id du customer en param

            $version = $versioningService->getVersion();
            $allUsersCustomer = $this->userRepository->findBy(['id_customer' => $id]);
            $context = SerializationContext::create()->setGroups(["getCustomerUsers"]);
            // $context->setVersion($version);
            $jsonList = $this->serializer->serialize($allUsersCustomer, 'json', $context );

            return new JsonResponse(
                $jsonList,
                Response::HTTP_OK,
                [],
                true
            );
            
        }else {

            return new JsonResponse(
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        }

    }

    #[Route('/api/customer/{id}/user/{userId}', name: 'UserFromCustomer', methods:['GET'])]
    public function getUserFromCustomer(int $id, int $userId, VersioningService $versioningService): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if($customer){

            // je dois faire une vérification si l'id du customer connecté == a l'id du customer en param

            $version = $versioningService->getVersion();
            $UserCustomer = $this->userRepository->find($userId);
            $context = SerializationContext::create()->setGroups(["getCustomerUsers"]);
            // $context->setVersion($version);
            $jsonList = $this->serializer->serialize($UserCustomer, 'json', $context );

            return new JsonResponse(
                $jsonList,
                Response::HTTP_OK,
                [],
                true
            );
            
        }else {

            return new JsonResponse(
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        }

    }


    #[Route('/api/customer/{id}/user', name: 'addUserFromCustomer', methods:['POST'])]
    public function addUserFromCustomer(Request $request, int $id, VersioningService $versioningService, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if($customer){

            // je dois faire une vérification si l'id du customer connecté == a l'id du customer en param

            $contextDeserialization = DeserializationContext::create()->setGroups(["addUser"]);
            $newUser = $this->serializer->deserialize($request->getContent(), User::class, 'json', $contextDeserialization);

            // on fera les gestions des erreurs plus tard
            $newUser->setIdCustomer($customer);
            
            $this->entityManager->persist($newUser);
            $this->entityManager->flush();

            $context = SerializationContext::create()->setGroups(["addUser"]);
            $jsonNewUser = $this->serializer->serialize($newUser, 'json', $context );
            $location = $urlGenerator->generate('UserFromCustomer', ['id' => $customer->getId() , 'userId' =>  $newUser->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            return new JsonResponse(
                $jsonNewUser,
                Response::HTTP_CREATED,
                ["Location" => $location],
                true
            );
            
        }else {

            return new JsonResponse(
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            );
        }

    }

    
}
