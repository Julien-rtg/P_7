<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

    #[Route('/api/products', name: 'listProducts', methods: ['GET'])]
    public function getListOfProducts(): JsonResponse
    {
        $allProducts = $this->productRepository->findAll();
        $context = SerializationContext::create()->setGroups(["getProducts"]);
        $jsonProductsList = $this->serializer->serialize($allProducts, 'json', $context);

        return new JsonResponse(
            $jsonProductsList,
            Response::HTTP_OK,
            [],
            true
        );
    }
    
    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])]
    public function getDetailOfProduct(Product $product): JsonResponse
    {
        // grace au param converter si je passe mon entity product en param il va chercher automatiquement mon id produit
        
        $context = SerializationContext::create()->setGroups(["getProducts"]);
        $jsonProduct= $this->serializer->serialize($product, 'json', $context);

        return new JsonResponse(
            $jsonProduct,
            Response::HTTP_OK,
            [],
            true
        );
        

        return new JsonResponse(
            null, 
            Response::HTTP_NOT_FOUND
        );
    }

}
