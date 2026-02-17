<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Customer;
use App\Entity\Enum\AccountStatus;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'user-admin';
    public const CUSTOMER_USER_REFERENCE = 'user-customer';
    public const CUSTOMER_REFERENCE = 'customer';

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Admin User
        $adminUser = new User();
        $adminUser->setEmail('mifaniapaolo0012@gmail.com');
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'password'));
        $adminUser->setRoles(['ROLE_ADMIN']);
        $manager->persist($adminUser);
        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser);

        $admin = new Admin();
        $admin->setFirstName('Bien Paolo');
        $admin->setLastName('Mifania');
        $admin->setAvatar('sample_avatar.jpeg');
        $admin->setUser($adminUser);
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($admin);


        // Customer User
        $customerUser = new User();
        $customerUser->setEmail('lopao0012@gmail.com');
        $customerUser->setPassword($this->passwordHasher->hashPassword($customerUser, 'password'));
        $customerUser->setRoles(['ROLE_USER']);
        $manager->persist($customerUser);
        $this->addReference(self::CUSTOMER_USER_REFERENCE, $customerUser);

        $customer = new Customer();
        $customer->setFirstName('Lopao');
        $customer->setLastName('Mifania');
        $customer->setContactNumber('09269332782');
        $customer->setAddress('Malatapay Maluay');
        $customer->setCity('Zamboanguita');
        $customer->setCountry('Philippines');
        $customer->setState('Negros Oriental');
        $customer->setRewardPoints(0);
        $customer->setAccountStatus(AccountStatus::Active);
        $customer->setUser($customerUser);
        $customer->setCreatedAt(new \DateTimeImmutable());
        $customer->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($customer);

        $this->addReference(self::CUSTOMER_REFERENCE, $customer);

        $manager->flush();
    }
}
