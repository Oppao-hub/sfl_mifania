<?php

namespace App\EventSubscriber;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\SubCategory;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

// The class will no longer implement EventSubscriberInterface

#[AsDoctrineListener(event: Events::prePersist)] // <--- ADD ATTRIBUTE for prePersist
#[AsDoctrineListener(event: Events::preUpdate)]  // <--- ADD ATTRIBUTE for preUpdate
class SluggerSubscriber // REMOVE 'implements EventSubscriberInterface'
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->setSlug($args->getObject());
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->setSlug($args->getObject());
        $this->recomputeChangeSet($args);
    }

    // src/EventSubscriber/SluggerSubscriber.php (Updated setSlug method)

    private function setSlug(object $entity): void
    {
        if (!$entity instanceof Product && !$entity instanceof Category && !$entity instanceof SubCategory) {
            return;
        }

        if (method_exists($entity, 'getName') && method_exists($entity, 'getSlug') && method_exists($entity, 'setSlug')) {
            $name = $entity->getName();
            $currentSlug = $entity->getSlug();

            // 1. Only proceed if the entity has a name
            if (empty($name)) {
                return;
            }

            $generatedSlug = $this->slugger->slug($name)->lower()->toString();

            // 2. Set the slug if it's brand new OR if the generated slug doesn't match the current slug
            //    (meaning the name has changed).
            if (empty($currentSlug) || $currentSlug !== $generatedSlug) {
                $entity->setSlug($generatedSlug);
            }
        }
    }

    private function recomputeChangeSet(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Product && !$entity instanceof Category && !$entity instanceof SubCategory) {
            return;
        }

        $entityManager = $args->getObjectManager();
        if (!$entityManager instanceof EntityManager) {
            return;
        }

        $uow = $entityManager->getUnitOfWork();
        $uow->recomputeSingleEntityChangeSet($entityManager->getClassMetadata(get_class($entity)), $entity);
    }
}
