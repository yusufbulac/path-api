<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\User;
use App\Repository\OrderProductRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class OrderService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OrderProductRepository
     */
    private $orderProductRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param OrderProductRepository $orderProductRepository
     */
    public function __construct(EntityManagerInterface $entityManager,
                                ProductRepository      $productRepository,
                                OrderProductRepository $orderProductRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
        $this->orderProductRepository = $orderProductRepository;
    }

    /**
     * @param $orderCode
     * @param $quantities
     * @param $address
     * @param $shippingDate
     * @param $productIds
     * @param User $user
     * @return Order
     */
    public function insertOrder($orderCode, $quantities, $address, $shippingDate, $productIds, User $user): Order
    {


        $order = new Order();

        $order->setOrderCode($orderCode);
        $order->setAddress($address);
        $order->setShippingDate(\DateTime::createFromFormat('Y-m-d', $shippingDate));
        $order->setUserOrdered($user);

        foreach ($productIds as $key => $productId) {

            $product = $this->productRepository->findOneBy(['id' => $productId]);

            if (!$product) {
                throw new BadRequestHttpException('Product ID Not Found');
            } else {
                $orderProduct = new OrderProduct();

                $orderProduct->setProduct($product);
                $orderProduct->setQuantity($quantities[$key]);

                $this->entityManager->persist($orderProduct);
                $order->addOrderProduct($orderProduct);
            }
        }

        $this->entityManager->persist($order);

        $this->entityManager->flush();

        return $order;

    }

    /**
     * @param Order $order
     * @param $quantities
     * @param $address
     * @param $productIds
     * @return Order
     */
    public function updateOrder(Order $order, $quantities, $address, $productIds): Order
    {

        (!$address) ?: $order->setAddress($address);

        foreach ($productIds as $key => $productId) {

            if ($productId && $productId != "") {

                $product = $this->productRepository->findOneBy(['id' => $productId]);

                if (!$product) {
                    throw new BadRequestHttpException('Product ID Not Found');
                } else {

                    $orderProduct = $this->orderProductRepository->findOneBy([
                        'product' => $product->getId(),
                        'orders' => $order->getId()
                    ]);

                    if (!$orderProduct) {
                        $orderProduct = new OrderProduct();
                        $orderProduct->setProduct($product);
                    }

                    $orderProduct->setQuantity($quantities[$key]);

                    $this->entityManager->persist($orderProduct);
                    $order->addOrderProduct($orderProduct);
                }
            }
        }


        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     */
    public function removeOrder(Order $order): Order
    {

        $this->entityManager->remove($order);
        $this->entityManager->flush();

        return $order;
    }
}