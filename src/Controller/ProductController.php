<?php

namespace KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Controller;

use KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Entity\Product;
use KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProductController
 * @package KrzysztofNikiel\RecruitmentTask\Controller\ProductController
 * @Route("/api", name="product_api")
 */
class ProductController extends AbstractController
{
    private const REQUIREMENTS = ProductRepository::IN_STOCK . '|' . ProductRepository::OUT_STOCK;

    /**
     * @param string $type
     * @return JsonResponse
     * @Route("/products/{type}", name="products", methods={"GET"}, defaults={"type"="in-stock"}, requirements={
     *     "type"=self::REQUIREMENTS;
     * })
     */
    public function getProductsByStockType(string $type): JsonResponse
    {
        /** @var ProductRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Product::class);
        return $this->response($repository->findProductByStock($type));
    }

//    /**
//     * @param Request $request
//     * @param EntityManagerInterface $entityManager
//     * @param ProductRepository $ProductRepository
//     * @return JsonResponse
//     * @throws \Exception
//     * @Route("/products", name="products_add", methods={"POST"})
//     */
//    public function addProduct(Request $request, EntityManagerInterface $entityManager, ProductRepository $ProductRepository)
//    {
//
//        try {
//            $request = $this->transformJsonBody($request);
//
//            if (!$request || !$request->get('name') || !$request->request->get('description')) {
//                throw new \Exception();
//            }
//
//            $Product = new Product();
//            $Product->setName($request->get('name'));
//            $Product->setAmount($request->get('amount'));
//            $entityManager->persist($Product);
//            $entityManager->flush();
//
//            $data = [
//                'status' => 200,
//                'success' => "Product added successfully",
//            ];
//            return $this->response($data);
//
//        } catch (\Exception $e) {
//            $data = [
//                'status' => 422,
//                'errors' => "Data no valid",
//            ];
//            return $this->response($data, 422);
//        }
//
//    }
//
//
//    /**
//     * @param ProductRepository $ProductRepository
//     * @param $id
//     * @return JsonResponse
//     * @Route("/Products/{id}", name="Products_get", methods={"GET"})
//     */
//    public function getProduct(ProductRepository $ProductRepository, $id)
//    {
//        $Product = $ProductRepository->find($id);
//
//        if (!$Product) {
//            $data = [
//                'status' => 404,
//                'errors' => "Product not found",
//            ];
//            return $this->response($data, 404);
//        }
//        return $this->response($Product);
//    }
//
//    /**
//     * @param Request $request
//     * @param EntityManagerInterface $entityManager
//     * @param ProductRepository $ProductRepository
//     * @param $id
//     * @return JsonResponse
//     * @Route("/Products/{id}", name="Products_put", methods={"PUT"})
//     */
//    public function updateProduct(Request $request, EntityManagerInterface $entityManager, ProductRepository $ProductRepository, $id)
//    {
//
//        try {
//            $Product = $ProductRepository->find($id);
//
//            if (!$Product) {
//                $data = [
//                    'status' => 404,
//                    'errors' => "Product not found",
//                ];
//                return $this->response($data, 404);
//            }
//
//            $request = $this->transformJsonBody($request);
//
//            if (!$request || !$request->get('name') || !$request->request->get('description')) {
//                throw new \Exception();
//            }
//
//            $Product->setName($request->get('name'));
//            $Product->setDescription($request->get('description'));
//            $entityManager->flush();
//
//            $data = [
//                'status' => 200,
//                'errors' => "Product updated successfully",
//            ];
//            return $this->response($data);
//
//        } catch (\Exception $e) {
//            $data = [
//                'status' => 422,
//                'errors' => "Data no valid",
//            ];
//            return $this->response($data, 422);
//        }
//
//    }
//
//
//    /**
//     * @param ProductRepository $ProductRepository
//     * @param $id
//     * @return JsonResponse
//     * @Route("/Products/{id}", name="Products_delete", methods={"DELETE"})
//     */
//    public function deleteProduct(EntityManagerInterface $entityManager, ProductRepository $ProductRepository, $id)
//    {
//        $Product = $ProductRepository->find($id);
//
//        if (!$Product) {
//            $data = [
//                'status' => 404,
//                'errors' => "Product not found",
//            ];
//            return $this->response($data, 404);
//        }
//
//        $entityManager->remove($Product);
//        $entityManager->flush();
//        $data = [
//            'status' => 200,
//            'errors' => "Product deleted successfully",
//        ];
//        return $this->response($data);
//    }


    /**
     * Returns a JSON response
     *
     * @param array $data
     * @param $status
     * @param array $headers
     * @return JsonResponse
     */
    private function response($data, $status = 200, $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }

}