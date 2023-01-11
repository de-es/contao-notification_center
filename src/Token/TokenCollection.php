<?php

declare(strict_types=1);

namespace Terminal42\NotificationCenterBundle\Token;

use Ramsey\Collection\AbstractCollection;

/**
 * @extends AbstractCollection<TokenInterface>
 */
class TokenCollection extends AbstractCollection
{
    public static function fromArray(array $data): self
    {
        $tokens = [];

        foreach ($data as $token) {
            if (!isset($token['class']) || !class_exists($token['class']) || !is_a($token['class'], TokenInterface::class, true)) {
                continue;
            }

            $tokens[] = $token['class']::fromArray($token['data'] ?? []);
        }

        return new self($tokens);
    }

    /**
     * @return array<string, string>
     */
    public function forSimpleTokenParser(): array
    {
        $data = [];

        /** @var TokenInterface $token */
        foreach ($this as $token) {
            $data[$token->getName()] = $token->getParserValue();
        }

        return $data;
    }

    public function getByName(string $name): TokenInterface|null
    {
        /** @var TokenInterface $token */
        foreach ($this as $token) {
            if ($token->getName() === $name) {
                return $token;
            }
        }

        return null;
    }

    public function has(string $name): bool
    {
        return null !== $this->getByName($name);
    }

    public function toArray(): array
    {
        $data = [];

        /** @var TokenInterface $token */
        foreach ($this as $token) {
            $data[] = [
                'class' => \get_class($token),
                'data' => $token->toArray(),
            ];
        }

        return $data;
    }

    public function getType(): string
    {
        return TokenInterface::class;
    }
}
