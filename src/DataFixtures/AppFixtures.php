<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{

    public const CUSTOMER_REF = 'customer-ref_%s';
    public const PRODUCT_REF = 'product-ref_%s';
    public const USER_REF = 'user-ref_%s';
    

    private $userPasswordHasherInterface;

    public function __construct(UserPasswordHasherInterface $userPasswordHasherInterface)
    {
        $this->userPasswordHasherInterface = $userPasswordHasherInterface;
    }

    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $this->productLoad($manager);
        $this->customerLoad($manager);
        $this->userLoad($manager);

        // $manager->flush();
    }

    public function productLoad(ObjectManager $manager){

        for ($i = 0; $i < 20; $i++) {
            $faker = Factory::create('fr_FR');
            $product = new Product();

            $product->setName($faker->word());
            $product->setDescription($faker->text());
            $product->setPicture($faker->firstName());
            $manager->persist($product);
        }
        $manager->flush();

    }
    public function customerLoad(ObjectManager $manager){
        $dataCustomerCollection = ["Orange", "Bouygues", "Sfr", "Free"];

        for ($i = 0; $i < count($dataCustomerCollection); $i++) {

            $customer = new Customer();

            $customer->setName($dataCustomerCollection[$i]);
            $customer->setEmail('admin@' . $dataCustomerCollection[$i] . 'api.com');
            $customer->setPassword($this->userPasswordHasherInterface->hashPassword($customer, "admin"));
            $customer->setRoles(["ROLE_ADMIN"]);
            $manager->persist($customer);
            $this->addReference(sprintf(self::CUSTOMER_REF, $i), $customer);
        }
        $manager->flush();
        

    }
    public function userLoad(ObjectManager $manager){
        for ($i = 0; $i < 10; $i++) {
            $faker = Factory::create('fr_FR');

            $user = new User();
            $user->setIdCustomer($this->getReference('customer-ref_0'));
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setEmail($faker->email());
            $user->setAddress($faker->randomDigitNot(0) . ' ' . $faker->name());
            $user->setCity($faker->words(1, true));
            $user->setZipCode(rand(10000, 95000));
            $manager->persist($user);
        }
        $manager->flush();

        for ($i = 10; $i < 20; $i++) {
            $faker = Factory::create('fr_FR');

            $user = new User();
            $user->setIdCustomer($this->getReference('customer-ref_1'));
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setEmail($faker->email());
            $user->setAddress($faker->randomDigitNot(0) . ' ' . $faker->name());
            $user->setCity($faker->words(1, true));
            $user->setZipCode(rand(10000, 95000));
            $manager->persist($user);
        }
        $manager->flush();
    }
}
