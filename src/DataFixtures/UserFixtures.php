<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Customer;
use App\Entity\Staff;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'user-admin';
    public const STAFF_USER_REFERENCE = 'user-staff';
    public const CUSTOMER_USER_REFERENCE = 'user-customer';
    public const CUSTOMER_REFERENCE = 'customer';

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // --- Admin User ---
        $adminUser = new User();
        $adminUser->setEmail('mifaniapaolo0012@gmail.com');
        $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'password'));
        $adminUser->setRoles(['ROLE_SUPER_ADMIN']);
        $adminUser->setIsVerified(true); // 👈 Bypasses email verification for local testing
        $manager->persist($adminUser);
        $this->addReference(self::ADMIN_USER_REFERENCE, $adminUser);

        $admin = new Admin();
        $admin->setFirstName('Bien Paolo');
        $admin->setLastName('Mifania');
        $admin->setAvatar('default_avatar.jpg');
        $admin->setUser($adminUser);
        $manager->persist($admin);

        $staffUser = new User();
        $staffUser->setEmail('staff@mifania.com');
        $staffUser->setPassword($this->passwordHasher->hashPassword($staffUser, 'password'));
        $staffUser->setRoles(['ROLE_STAFF']);
        $staffUser->setIsVerified(true);
        $manager->persist($staffUser);
        $this->addReference(self::STAFF_USER_REFERENCE, $staffUser);

        // Create a staff member
        $staff = new Staff();
        $staff->setFirstName('John');
        $staff->setLastName('Doe');
        $staff->setAvatar('default_avatar.jpg');
        $staff->setUser($staffUser);
        $manager->persist($staff);

        // --- Customer User ---
        $customerUser = new User();
        $customerUser->setEmail('lopao0012@gmail.com');
        $customerUser->setPassword($this->passwordHasher->hashPassword($customerUser, 'password'));
        $customerUser->setRoles(['ROLE_CUSTOMER']); // 👈 Matched to your RegistrationController!
        $customerUser->setIsVerified(true); // 👈 Bypasses email verification
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
        $customer->setUser($customerUser);
        $manager->persist($customer);

        $this->addReference(self::CUSTOMER_REFERENCE, $customer);

        $manager->flush();
    }
}
