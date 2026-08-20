<?php

namespace App\Features\Checkout\DTOs;

class QRCodePayload
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $eventId,
        public readonly string $userId,
        public readonly string $email,
        public readonly string $tier,
        public readonly \DateTimeInterface $expiresAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ticketId: $data['ticket_id'],
            eventId: $data['event_id'],
            userId: $data['user_id'],
            email: $data['email'],
            tier: $data['tier'],
            expiresAt: new \DateTimeImmutable($data['expires_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'event_id' => $this->eventId,
            'user_id' => $this->userId,
            'email' => $this->email,
            'tier' => $this->tier,
            'expires_at' => $this->expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
