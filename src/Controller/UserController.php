<?php

namespace App\Controller;

use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use App\Service\VersioningService;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    protected $customerRepository;
    protected $userRepository;
    protected $serializer;

    public function __construct(
        CustomerRepository $customerRepository,
        UserRepository $userRepository,
        SerializerInterface $serializerInterface,
    ) {
        $this->customerRepository = $customerRepository;
        $this->userRepository = $userRepository;
        $this->serializer = $serializerInterface;
    }

    #[Route('/api/customer/{id}/users', name: 'AllUsersFromCustomer', methods:['GET'])]
    public function getAllUsersFromCustomer(int $id, VersioningService $versioningService): JsonResponse
    {
        $customer = $this->customerRepository->find($id);

        if($customer){

            // je dois faire une vérification si l'id du customer connecté == a l'id du customer en param

            $version = $versioningService->getVersion();
            $allUsersCustomer = $this->userRepository->getAllCustomerUsers($id);
            $context = SerializationContext::create()->setGroups(["getCustomerUsers"]);
            // $context->setVersion($version);
            $jsonList = $this->serializer->serialize($allUsersCustomer, 'json', $context );

            return new JsonResponse([
                $jsonList,
                Response::HTTP_OK,
                [],
                true
            ]);
            
        }else {

            return new JsonResponse([
                ['message' => 'This customer does not exist'],
                Response::HTTP_NOT_FOUND
            ]);
        }

    }

    
}
