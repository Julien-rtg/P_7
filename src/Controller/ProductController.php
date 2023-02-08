<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{

    protected $productRepository;
    protected $serializer;

    public function __construct(
        ProductRepository $productRepository,
        SerializerInterface $serializerInterface
        )
    {
        $this->productRepository = $productRepository;
        $this->serializer = $serializerInterface;
    }

    #[Route('/api/product', name: 'list_products', methods: ['GET'])]
    public function getListOfProducts(): JsonResponse
    {
        $allProducts = $this->productRepository->findAll();
        $jsonBookList = $this->serializer->serialize($allProducts, 'json', ['groups' => 'getProducts']);

        return new JsonResponse([
            $jsonBookList,
            Response::HTTP_OK,
            [],
            true
        ]);
    }
    
    // #[Route('/api/product', name: 'list_products', methods: ['GET'])]
    // public function getDetailOfProduct(): JsonResponse
    // {
    //     $allProducts = $this->productRepository->findAll();
    //     $jsonBookList = $this->serializer->serialize($allProducts, 'json');

    //     return new JsonResponse([
    //         $jsonBookList,
    //         Response::HTTP_OK,
    //         [],
    //         true
    //     ]);
    // }

}
