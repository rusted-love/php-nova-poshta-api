<?php
/**
 * @noinspection UnknownInspectionInspection
 * @noinspection PhpUnused
 */
declare(strict_types=1);

namespace BladL\NovaPoshta\Types;

use BladL\NovaPoshta\Exceptions\UnexpectedCounterpartyKind;
use function in_array;
use JetBrains\PhpStorm\Pure;

final class CounterpartyKind
{
    private string $type;
    private const SENDER = 'Sender';
    private const RECIPIENT = 'Recipient';
    private const THIRD_PERSON = 'ThirdPerson';

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @throws UnexpectedCounterpartyKind
     */
    public static function fromString(string $type): self
    {
        if (in_array($type, [self::SENDER, self::RECIPIENT, self::THIRD_PERSON], true)) {
            return new self($type);
        }
        throw new UnexpectedCounterpartyKind($type);
    }

    #[Pure]
     public static function sender(): self
     {
         return new self(self::SENDER);
     }

    #[Pure]
    public static function recipient(): self
    {
        return new self(self::RECIPIENT);
    }

    #[Pure]
     public static function thirdPerson(): self
     {
         return new self(self::THIRD_PERSON);
     }

    public function toString(): string
    {
        return $this->type;
    }
}