<?php

namespace App\Support\Scramble;

use App\Support\PaginatedResponse;
use Dedoc\Scramble\Infer\Extensions\Event\StaticMethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\StaticMethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Contracts\LiteralString;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\UnknownType;

/**
 * `PaginatedResponse::make($paginator, SomeResource::class)` devolve um
 * `array` genérico em PHP — a análise estática por defeito do Scramble não
 * consegue seguir o `$resourceClass::collection(...)->resolve()` dinâmico lá
 * dentro (fica "string"). Esta extensão diz explicitamente ao Scramble a
 * forma real: `{data: SomeResource[], meta: {...}}`, lida de uma vez para
 * todos os ~50 controllers que usam este helper (ver PaginatedResponse.php).
 */
class PaginatedResponseTypeExtension implements StaticMethodReturnTypeExtension
{
    public function shouldHandle(string $name): bool
    {
        return $name === PaginatedResponse::class;
    }

    public function getStaticMethodReturnType(StaticMethodCallEvent $event): ?Type
    {
        if ($event->getName() !== 'make') {
            return null;
        }

        $resourceClassType = $event->getArg('resourceClass', 1);
        $resourceClass = $resourceClassType instanceof LiteralString
            ? $resourceClassType->getValue()
            : null;

        $dataItemType = $resourceClass && class_exists($resourceClass)
            ? new ObjectType($resourceClass)
            : new UnknownType('não foi possível resolver a classe do resource passada a PaginatedResponse::make()');

        return new KeyedArrayType([
            new ArrayItemType_('data', new ArrayType(value: $dataItemType)),
            new ArrayItemType_('meta', new KeyedArrayType([
                new ArrayItemType_('currentPage', new IntegerType),
                new ArrayItemType_('perPage', new IntegerType),
                new ArrayItemType_('total', new IntegerType),
                new ArrayItemType_('lastPage', new IntegerType),
            ])),
        ]);
    }
}
