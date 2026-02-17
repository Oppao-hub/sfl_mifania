<?php

namespace App\Entity\Enum;

enum StockStatus: string
{
    case IN_STOCK = 'In Stock';
    case OUT_OF_STOCK = 'Out of Stock';
    case LOW_STOCK = 'Low Stock';
}
