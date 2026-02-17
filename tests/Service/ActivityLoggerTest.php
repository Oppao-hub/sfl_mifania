<?php

namespace App\Tests\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ActivityLoggerTest extends TestCase
{
    public function testLog(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (ActivityLog $activityLog) {
                $this->assertSame('test_action', $activityLog->getAction());
                $this->assertSame('test_table', $activityLog->getTableName());
                $this->assertSame('test_description', $activityLog->getDescription());
                $this->assertSame('test_role', $activityLog->getRole());
                $this->assertNotNull($activityLog->getUser());
                return true;
            }));
        $entityManager->expects($this->once())
            ->method('flush');

        $activityLogger = new ActivityLogger($entityManager);

        $user = new User();
        $activityLogger->log('test_action', 'test_table', 'test_description', 'test_role', $user);
    }
}
