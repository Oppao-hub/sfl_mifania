<?Php

namespace App\DTO;

use App\Entity\Enum\Color;
use App\Entity\Enum\Size;

class CartItemDTO
{
    public int $quantity;
    public ?Size $size;
    public ?Color $color;
    public ?string $action;
}
