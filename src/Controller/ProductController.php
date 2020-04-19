<?php
declare(strict_types=1);

namespace KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Controller;

use KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Entity\Product;
use KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

/**
 * Class ProductController
 * @package KrzysztofNikiel\RecruitmentTask\Controller\ProductController
 * @Route("/api", name="product_api")
 */
class ProductController extends AbstractController
{
    public const REQUIREMENTS = ProductRepository::IN_STOCK . '|' . ProductRepository::OUT_STOCK;

    /**
     * @param string $type
     * @param ProductRepository $productRepository
     *
     * @return JsonResponse
     * @Route("/products/{type}", name="products", methods={"GET"}, defaults={"type"="in-stock"}, requirements={
     *     "type"=ProductController::REQUIREMENTS
     * })
     */
    public function getProductsByStockType($type, $productRepository): JsonResponse
    {
        return $this->response($productRepository->findProductByStock($type));
    }

    /**
     * @param Request $request
     * @param ProductRepository $ProductRepository
     * @return JsonResponse
     * @throws \Exception
     * @Route("/products", name="products_add", methods={"POST"})
     */
    public function addProduct(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $request = $this->transformJsonBody($request);

        if ($request->get('name') === null && $request->get('amount') === null) {
            return $this->response(
                [
                    'success' => false,
                    'message' => "Missing param",
                ],
                400
            );
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->getConnection()->beginTransaction();
        try {
            $product = new Product();
            $product->setName($request->get('name'));
            $product->setAmount($request->get('amount'));
            $entityManager->persist($product);
            $entityManager->flush();
            $entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            return $this->response(
                [
                    'success' => false,
                    'message' => "Action failed",
                ],
                500
            );
        }

        return $this->response([
            'success' => true,
            'message' => "Product added successfully",
        ]);
    }
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
    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @Route("/product/{id}", name="products_patch", methods={"patch"})
     */
    public function updateProduct(Request $request, $id): JsonResponse
    {
        /** @var ProductRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Product::class);
        $product = $repository->findOneBy(['id' => $id]);
        if ($product === null) {
            return $this->response(
                [
                    'success' => false,
                    'message' => "Product not found",
                ],
                404
            );
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->getConnection()->beginTransaction();
        $request = $this->transformJsonBody($request);

        /** @var Validation $validator */
        $validator = Validation::createValidator();
        $validator->validate(
            [
                'name' => $request->get('name'),
                'amount' => $request->get('amount')
            ],
            new Assert\Collection([
                'name' => new Assert\Length(['max' => 100]),
                'amount' => new Assert\Type(['type' => 'integer']),
            ])
        );


        if ($request->get('name') === null && $request->get('amount') === null) {
            return $this->response(
                [
                    'success' => false,
                    'message' => "Missing param",
                ],
                400
            );
        }
        try {
            $product->setName($request->get('name') ?: $product->getName());
            if ($request->get('amount') !== null) {
                $product->setAmount($request->get('amount'));
            }
            $entityManager->flush();
            $entityManager->getConnection()->commit();

        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            return $this->response(
                [
                    'success' => false,
                    'message' => "Action failed",
                ],
                500
            );
        }
        return $this->response(
            [
                'success' => true,
                'message' => "Product updated successfully",
            ],
        );
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @Route("/product/{id}", name="product_delete", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function deleteProduct($id)
    {
        /** @var ProductRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Product::class);
        $product = $repository->findOneBy(['id' => $id]);
        if ($product === null) {
            return $this->response(
                [
                    'success' => false,
                    'message' => "Product not found",
                ],
                404
            );
        }
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->getConnection()->beginTransaction();
        try {
            $entityManager->remove($product);
            $entityManager->flush();
            $entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $entityManager->getConnection()->rollBack();
            return $this->response(
                [
                    'success' => false,
                    'message' => "Action failed",
                ],
                500
            );
        }

        return $this->response(
            [
                'success' => true,
                'message' => "Product deleted successfully",
            ],
        );
    }


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

    /**
     * @param Request $request
     * @return Request
     */
    private function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request): Request
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }

}