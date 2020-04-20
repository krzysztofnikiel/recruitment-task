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
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
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
        $request = $this->transformJsonBody($request);
        $validator = Validation::createValidatorBuilder()->getValidator();
        $violations = $this->validateRequestData($request);

        if (count($violations) > 0) {
            return $this->response(
                [
                    'success' => false,
                    'message' => 'Validation errors',
                    'validation_errors' => $this->getValidationErrors($violations)
                ],
                400
            );
        }

        $entityManager = $this->getDoctrine()->getManager();
        /** @var Connection $connection */
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $product = new Product();
            $product->setName($request->get('name'));
            $product->setAmount((int)$request->get('amount'));

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

        $request = $this->transformJsonBody($request);
        $violations = $this->validateRequestData($request);
        if (count($violations) > 0) {
            return $this->response(
                [
                    'success' => false,
                    'message' => 'Validation errors',
                    'validation_errors' => $this->getValidationErrors($violations)
                ],
                400
            );
        }

        $entityManager = $this->getDoctrine()->getManager();
        /** @var Connection $connection */
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $product->setName($request->get('name') ?: $product->getName());
            if ($request->get('amount') !== null) {
                $product->setAmount((int)$request->get('amount'));
            }

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

    /**
     * @param Request $request
     * @return Request
     */
    private function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }

    /**
     * @param Request $request
     * @return ConstraintViolationListInterface
     */
    private function validateRequestData(Request $request): ConstraintViolationListInterface
    {
        /** @var ValidatorInterface $validator */
        $validator = Validation::createValidatorBuilder()->getValidator();
        return $validator->startContext()
            ->atPath('name')->validate($request->get('name'), [
                new NotNull(),
                new Length(['min' => 2, 'max' => 100]),
            ])
            ->atPath('amount')->validate($request->get('amount'), [
                new NotNull(),
                new Type('integer')
            ])
            ->getViolations();
    }

    /**
     * @param ConstraintViolationListInterface $violations
     * @return array
     */
    private function getValidationErrors(ConstraintViolationListInterface $violations): array
    {
        $validationErrors = [];
        foreach ($violations as $violation) {
            $validationErrors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $validationErrors;
    }
}