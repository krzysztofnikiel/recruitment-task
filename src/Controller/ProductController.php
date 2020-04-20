<?php
declare(strict_types=1);

namespace KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException as ConnectionExceptionAlias;
use KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Entity\Product;
use KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     *
     * @return JsonResponse
     * @Route("/products/{type}", name="products", methods={"GET"}, defaults={"type"="in-stock"}, requirements={
     *     "type"=ProductController::REQUIREMENTS
     * })
     */
    public function getProductsByStockType($type): JsonResponse
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        return $this->response($productRepository->findProductByStock($type));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     * @Route("/product", name="products_add", methods={"POST"})
     */
    public function addProduct(Request $request): JsonResponse
    {
        $product = new Product();
        $product->setName($request->get('name'));
        $product->setAmount((int)$request->get('amount'));

        /** @var ValidatorInterface $validator */
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
        $violations = $validator->validate($product);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $message[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->response(
                [
                    'success' => false,
                    'message' => $message,
                ],
                400
            );
        }

        $entityManager = $this->getDoctrine()->getManager();
        /** @var Connection $connection */
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $entityManager->persist($product);
            $entityManager->flush();
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
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
            'product' => $product->toArray()
        ]);
    }

    /**
     * @return JsonResponse
     * @Route("/products-more-than-five", name="products_get_more_than_five", methods={"GET"})
     */
    public function getProductByAmountMoreThanFive(): JsonResponse
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        return $this->response($productRepository->findProductMoreThanAmount(5));
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @Route("/product/{id}", name="products_patch", methods={"patch"}, requirements={"id"="\d+"})
     * @throws ConnectionExceptionAlias
     */
    public function updateProduct(Request $request, $id): JsonResponse
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->getDoctrine()->getRepository(Product::class);
        $product = $productRepository->findOneBy(['id' => $id]);
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
        if ($request->get('name') === null && $request->get('amount') === null) {
            return $this->response(
                [
                    'success' => false,
                    'message' => "Missing param",
                ],
                400
            );
        }
        $product->setName($request->get('name') ?: $product->getName());
        if ($request->get('amount') !== null) {
            $product->setAmount((int)$request->get('amount'));
        }

        /** @var ValidatorInterface $validator */
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
        $violations = $validator->validate($product);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $message[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->response(
                [
                    'success' => false,
                    'message' => $message,
                ],
                400
            );
        }
        /** @var Connection $connection */
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $entityManager->flush();
            $connection->commit();

        } catch (\Exception $e) {
            $connection->rollBack();
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
                'product' => $product->toArray()
            ]
        );
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @throws ConnectionExceptionAlias
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
        /** @var Connection $connection */
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $entityManager->remove($product);
            $entityManager->flush();
            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
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
            ]
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

}