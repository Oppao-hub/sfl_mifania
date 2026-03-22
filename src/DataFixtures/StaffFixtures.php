<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Staff;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class StaffFixtures extends Fixture
{
     private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create a user
        $user = new User();
        $user->setEmail('john.doe@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_STAFF']);
        $user->setIsVerified(true);
        $manager->persist($user);

        // Create a staff member
        $staff = new Staff();
        $staff->setFirstName('John');
        $staff->setLastName('Doe');
        $manager->persist($staff);

        // Link the staff member to the user
        $staff->setUser($user);
        $user->setStaff($staff);

        // Persist both entities
        $manager->persist($staff);
        $manager->persist($user);

        $manager->flush();
    }
}
