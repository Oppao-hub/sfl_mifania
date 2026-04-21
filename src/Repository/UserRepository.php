<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findAdmin(): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role OR u.roles LIKE :super')
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->setParameter('super', '%"ROLE_SUPER_ADMIN"%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

    }

    /**
     * @return User[] Returns an array of Admin users
     */
    public function findAllAdmins(): array
    {
        return $this->createQueryBuilder('u')
            // Use LIKE instead of JSON_CONTAINS
            ->andWhere('u.roles LIKE :role OR u.roles LIKE :super')
            // Wrap the role in quotes and wildcard % signs to match the JSON string
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->setParameter('super', '%"ROLE_SUPER_ADMIN"%')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[] Returns an array of Admin and Staff users
     */
    public function findAllManagement(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :admin OR u.roles LIKE :staff OR u.roles LIKE :super')
            ->setParameter('admin', '%"ROLE_ADMIN"%')
            ->setParameter('staff', '%"ROLE_STAFF"%')
            ->setParameter('super', '%"ROLE_SUPER_ADMIN"%')
            ->getQuery()
            ->getResult();
    }
}
