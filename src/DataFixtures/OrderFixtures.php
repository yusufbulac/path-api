<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function load(ObjectManager $manager): void
    {
        //FOR USER TABLE
        $userEmails = ['customer1@gmail.com', 'customer2@gmail.com', 'customer3@gmail.com'];
        $password = '$2y$13$1BWD5g/cz8vpHXlcYzDelOvVKS9h4HxdBLbVvwzOFd9LLFEXQb1W.'; //customer
        foreach ($userEmails as $userEmail) {

            $user = new User();
            $user
                ->setEmail($userEmail)
                ->setPassword($password)
                ->setCompany('ABC Company')
                ->setRoles(['ROLE_CUSTOMER']
                );
            $manager->persist($user);
        }

        //FOR PRODUCT TABLE
        $productNames = ['iPhone 7', 'iPhone 8', 'iPhone X', 'iPhone 11', 'iPhone 10', 'iPhone 12'];
        foreach ($productNames as $productName) {
            $product = new Product();
            $product
                ->setName($productName);
            $manager->persist($product);
        }

        $manager->flush();

        //order:id->1 for php unit - start
        $order = new Order();
        $order->setOrderCode('CodeTest');
        $order->setAddress('Berlin');
        $order->setShippingDate(new \DateTime('+10 days'));
        $order->setUserOrdered($this->entityManager->getRepository(User::class)->findOneBy(['id' => 1]));

        $orderProduct = new OrderProduct();
        $orderProduct->setProduct($this->entityManager->getRepository(Product::class)->findOneBy(['id' => 1]));
        $orderProduct->setQuantity(20);

        $manager->persist($orderProduct);
        $order->addOrderProduct($orderProduct);
        $manager->persist($order);
        $manager->flush();
        //order:id->1 for php unit - end


        //FOR ORDER TABLE
        $address = ['İstanbul', 'Ankara', 'İzmir', 'Kocaeli'];
        for ($i = 1; $i <= 9; $i++) {

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => rand(1, 3)]);
            $date = new \DateTime(sprintf('%d days', rand(-5, 5)));

            $order = new Order();
            $order->setOrderCode(sprintf('Code00%s', $i));
            $order->setAddress($address[rand(0, 3)]);
            $order->setShippingDate($date);
            $order->setUserOrdered($user);

            $holder = [];
            $productNumbers = rand(1, 6); // Each order will contain 1-6 products
            for ($x = 0; $x < $productNumbers; $x++) {

                $id = rand(1, 6);

                if (!in_array($id, $holder)) {
                    $product = $this->entityManager->getRepository(Product::class)->findOneBy(['id' => $id]);

                    //FOR ORDER_PRODUCT TABLE

                    $orderProduct = new OrderProduct();
                    $orderProduct->setProduct($product);
                    $orderProduct->setQuantity(rand(3, 15));

                    $manager->persist($orderProduct);
                    $order->addOrderProduct($orderProduct);
                    array_push($holder, $id);
                }
            }
            $manager->persist($order);
        }
        $manager->flush();
    }
}
