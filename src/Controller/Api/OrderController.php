<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route ("/api/order", name = "api_order")
 */
class OrderController extends AbstractController
{

    /**
     * @Route ("/list", name = "list", methods={"GET"})
     * @param OrderRepository $orderRepository
     * @return JsonResponse
     */
    public function listOrder(OrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findAll();
        $orderResponse = [];
        foreach ($orders as $key => $order) {

            $orderResponse[$key]['order']['id'] = $order->getId();
            $orderResponse[$key]['order']['orderCode'] = $order->getOrderCode();
            $orderResponse[$key]['order']['address'] = $order->getAddress();
            $orderResponse[$key]['order']['shippingDate'] = $order->getShippingDate()->format('d-m-Y');

            $orderResponse[$key]['user']['id'] = $order->getUsers()->getId();
            $orderResponse[$key]['user']['email'] = $order->getUsers()->getEmail();
            $orderResponse[$key]['user']['company'] = $order->getUsers()->getCompany();

            $products = $order->getOrderProducts();
            foreach ($products as $index => $product) {
                $orderResponse[$key]['product'][$index]['id'] = $product->getProduct()->getId();
                $orderResponse[$key]['product'][$index]['name'] = $product->getProduct()->getName();
                $orderResponse[$key]['product'][$index]['quantity'] = $product->getQuantity();
            }
        }
        return new JsonResponse($orderResponse, Response::HTTP_OK);
    }

    /**
     * @Route ("/detail/{id}", name = "detail", methods={"GET"})
     * @param Order $order
     * @return JsonResponse
     */
    public function detailOrder(Order $order): JsonResponse
    {
        $orderResponse = [];

        $orderResponse['order']['id'] = $order->getId();
        $orderResponse['order']['orderCode'] = $order->getOrderCode();
        $orderResponse['order']['address'] = $order->getAddress();
        $orderResponse['order']['shippingDate'] = $order->getShippingDate()->format('d-m-Y');

        $orderResponse['user']['id'] = $order->getUsers()->getId();
        $orderResponse['user']['email'] = $order->getUsers()->getEmail();
        $orderResponse['user']['company'] = $order->getUsers()->getCompany();

        $products = $order->getOrderProducts();
        foreach ($products as $index => $product) {
            $orderResponse['product'][$index]['id'] = $product->getProduct()->getId();
            $orderResponse['product'][$index]['name'] = $product->getProduct()->getName();
            $orderResponse['product'][$index]['quantity'] = $product->getQuantity();
        }
        return new JsonResponse($orderResponse, Response::HTTP_OK);
    }

    /**
     * @Route ("/add", name = "add", methods={"POST"})
     * @param Request $request
     * @param OrderService $orderService
     * @param Security $security
     * @return JsonResponse
     */
    public function addOrder(Request $request, OrderService $orderService, Security $security): JsonResponse
    {

        $orderCode = $request->request->get('orderCode');
        $address = $request->request->get('address');
        $shippingDate = $request->request->get('shippingDate');
        $user = $security->getUser();
        $productIds = explode(',', $request->request->get('productId'));
        $quantities = explode(',', $request->request->get('quantity'));

        $checkProducts = array_count_values($productIds);
        foreach ($checkProducts as $checkProduct) {
            if ($checkProduct > 1) {
                throw new BadRequestHttpException('Each product ID must be given once.');
            }
        }

        if (count($productIds) != count($quantities)) {
            throw new BadRequestHttpException('The quantity for each product should be specified in order.');
        } elseif (!$orderCode || !$quantities || !$address || !$shippingDate || !$productIds || !$user) {
            throw new BadRequestHttpException('The value for one of fields in the request body was invalid or null.');
        }


        $order = $orderService->insertOrder($orderCode, $quantities, $address, $shippingDate, $productIds, $user);

        $products = $order->getOrderProducts();
        $productsResponse = [];

        foreach ($products as $key => $product) {
            $productsResponse[$key]['id'] = $product->getProduct()->getId();
            $productsResponse[$key]['name'] = $product->getProduct()->getName();
            $productsResponse[$key]['quantity'] = $product->getQuantity();
        }

        return new JsonResponse(
            [
                'order' => [
                    'id' => $order->getId(),
                    'orderCode' => $order->getOrderCode(),
                    'address' => $order->getAddress(),
                    'shippingDate' => $order->getShippingDate()->format('d-m-Y')
                ],
                'products' => $productsResponse
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * @Route ("/update/{id}", name = "update", methods={"PUT"})
     * @param Order|null $order
     * @param Request $request
     * @param OrderService $orderService
     * @param Security $security
     * @return JsonResponse
     */
    public function updateOrder(?Order $order, Request $request, OrderService $orderService, Security $security): JsonResponse
    {

        $address = $request->request->get('address');
        $user = $security->getUser();

        $productIds = explode(',', $request->request->get('productId'));
        $quantities = explode(',', $request->request->get('quantity'));

        if($productIds[0] == null){
            unset($productIds[0]);
        }

        if($quantities[0] == null){
            unset($quantities[0]);
        }

        $checkProducts = array_count_values($productIds);
        foreach ($checkProducts as $checkProduct) {
            if ($checkProduct > 1) {
                throw new BadRequestHttpException('Each product ID must be given once.');
            }
        }

        if (!$order) {
            throw new NotFoundHttpException("Order ID Not Found");
        } elseif (count($productIds) != count($quantities)) {
            throw new BadRequestHttpException('The quantity for each product should be specified in order.');
        } elseif ((!$quantities && !$productIds) && !$address) {
            throw new BadRequestHttpException('The value for one of fields in the request body was invalid or null.');
        } elseif ($user->getId() != $order->getUsers()->getId()) {
            throw new AccessDeniedHttpException('You can only update your own orders.');
        }

        $shippingDate = $order->getShippingDate();
        if ($shippingDate > new \DateTime()) {
            $order = $orderService->updateOrder($order, $quantities, $address, $productIds);
        } else {
            throw new BadRequestHttpException('Orders past the shipping date cannot be updated');
        }

        $products = $order->getOrderProducts();
        $productsResponse = [];

        foreach ($products as $key => $product) {
            $productsResponse[$key]['id'] = $product->getProduct()->getId();
            $productsResponse[$key]['name'] = $product->getProduct()->getName();
            $productsResponse[$key]['quantity'] = $product->getQuantity();
        }

        return new JsonResponse(
            [
                'order' => [
                    'id' => $order->getId(),
                    'orderCode' => $order->getOrderCode(),
                    'address' => $order->getAddress(),
                    'shippingDate' => $order->getShippingDate()->format('d-m-Y')
                ],
                'products' => $productsResponse
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @Route ("/delete/{id}", name = "delete", methods={"DELETE"})
     * @param Order $order
     * @param Security $security
     * @param OrderService $orderService
     * @return JsonResponse
     */
    public function deleteOrder(Order $order, Security $security, OrderService $orderService): JsonResponse
    {
        $user = $security->getUser();

        if (!$order) {
            throw new NotFoundHttpException("Order ID Not Found");
        } elseif ($user->getId() != $order->getUsers()->getId()) {
            throw new AccessDeniedHttpException('You can only delete your own orders.');
        } else {
            $orderService->removeOrder($order);
        }

        return new JsonResponse("Order deleted", Response::HTTP_NO_CONTENT);
    }
}