<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $productRepository;
    private string $path = '/product/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->productRepository = $this->manager->getRepository(Product::class);

        foreach ($this->productRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Product index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'product[productName]' => 'Testing',
            'product[category]' => 'Testing',
            'product[description]' => 'Testing',
            'product[price]' => 'Testing',
            'product[stockQuantity]' => 'Testing',
            'product[size]' => 'Testing',
            'product[color]' => 'Testing',
            'product[material]' => 'Testing',
            'product[sustainabilityTag]' => 'Testing',
            'product[status]' => 'Testing',
            'product[dateAdded]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->productRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Product();
        $fixture->setProductName('My Title');
        $fixture->setCategory('My Title');
        $fixture->setDescription('My Title');
        $fixture->setPrice('My Title');
        $fixture->setStockQuantity('My Title');
        $fixture->setSize('My Title');
        $fixture->setColor('My Title');
        $fixture->setMaterial('My Title');
        $fixture->setSustainabilityTag('My Title');
        $fixture->setStatus('My Title');
        $fixture->setDateAdded('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Product');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Product();
        $fixture->setProductName('Value');
        $fixture->setCategory('Value');
        $fixture->setDescription('Value');
        $fixture->setPrice('Value');
        $fixture->setStockQuantity('Value');
        $fixture->setSize('Value');
        $fixture->setColor('Value');
        $fixture->setMaterial('Value');
        $fixture->setSustainabilityTag('Value');
        $fixture->setStatus('Value');
        $fixture->setDateAdded('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'product[productName]' => 'Something New',
            'product[category]' => 'Something New',
            'product[description]' => 'Something New',
            'product[price]' => 'Something New',
            'product[stockQuantity]' => 'Something New',
            'product[size]' => 'Something New',
            'product[color]' => 'Something New',
            'product[material]' => 'Something New',
            'product[sustainabilityTag]' => 'Something New',
            'product[status]' => 'Something New',
            'product[dateAdded]' => 'Something New',
        ]);

        self::assertResponseRedirects('/product/');

        $fixture = $this->productRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getProductName());
        self::assertSame('Something New', $fixture[0]->getCategory());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getPrice());
        self::assertSame('Something New', $fixture[0]->getStockQuantity());
        self::assertSame('Something New', $fixture[0]->getSize());
        self::assertSame('Something New', $fixture[0]->getColor());
        self::assertSame('Something New', $fixture[0]->getMaterial());
        self::assertSame('Something New', $fixture[0]->getSustainabilityTag());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getDateAdded());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Product();
        $fixture->setProductName('Value');
        $fixture->setCategory('Value');
        $fixture->setDescription('Value');
        $fixture->setPrice('Value');
        $fixture->setStockQuantity('Value');
        $fixture->setSize('Value');
        $fixture->setColor('Value');
        $fixture->setMaterial('Value');
        $fixture->setSustainabilityTag('Value');
        $fixture->setStatus('Value');
        $fixture->setDateAdded('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/product/');
        self::assertSame(0, $this->productRepository->count([]));
    }
}
