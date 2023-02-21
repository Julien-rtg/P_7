<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ProductController extends AbstractController
{

    protected $productRepository;
    protected $serializer;
    protected $cachePool;

    public function __construct(
        ProductRepository $productRepository,
        SerializerInterface $serializerInterface,
        TagAwareCacheInterface $cachePool
        )
    {
        $this->productRepository = $productRepository;
        $this->serializer = $serializerInterface;
        $this->cachePool = $cachePool;
    }

    /** 
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des produits",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProducts"}))
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
     * @OA\Tag(name="Products")
     */
    #[Route('/api/products', name: 'listProducts', methods: ['GET'])]
    public function getListOfProducts(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getListOfProducts-" . $page . "-" . $limit;
        $allProducts = $this->cachePool->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit) {
            $item->tag("productsCache");
            return $productRepository->findAllWithPagination($page, $limit);
        });

        $context = SerializationContext::create()->setGroups(["getProducts"]);
        $jsonProductsList = $this->serializer->serialize($allProducts, 'json', $context);

        return new JsonResponse(
            $jsonProductsList,
            Response::HTTP_OK,
            [],
            true
        );
    }

    /** 
     * @OA\Response(
     *     response=200,
     *     description="Retourne détail d'un produit",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class, groups={"getProducts"}))
     *     )
     * )
     * @OA\Tag(name="Products")
     */
    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])]
    public function getDetailOfProduct(int $id, ProductRepository $productRepository): JsonResponse
    {

        $product = $this->productRepository->find($id);
        if ($product === null) {
            return new JsonResponse(['message' => 'This product does not exist'], Response::HTTP_NOT_FOUND);
        }

        $idCache = "getDetailOfProduct-" . $id;
        $detailProduct = $this->cachePool->get($idCache, function (ItemInterface $item) use ($productRepository, $id) {
            $item->tag("detailProductCache");
            return $productRepository->find($id);
        });

        $context = SerializationContext::create()->setGroups(["getProducts"]);
        $jsonProduct= $this->serializer->serialize($detailProduct, 'json', $context);

        return new JsonResponse(
            $jsonProduct,
            Response::HTTP_OK,
            [],
            true
        );
        
    }

}
