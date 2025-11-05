<?php
// src/Twig/Components/CustomerCardComponent.php
namespace App\Twig\Components;

use App\Entity\Customer;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('customer_card')]
class CustomerCard
{
    // Make the property public, don't use constructor injection for entities
    public Customer $customer;

    // Optional: You can also set default values if needed
    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }
}
