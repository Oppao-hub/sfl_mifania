<?php

namespace App\Entity\Enum;

enum StockStatus: string
{
    case InStock = 'In Stock';
    case OutOfStock = 'Out of Stock';
    case LowStock = 'Low Stock';
}
