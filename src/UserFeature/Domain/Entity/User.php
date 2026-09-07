<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\Entity;

use App\UserFeature\Domain\Event\PasswordChanged;
use App\UserFeature\Domain\Event\PasswordResetRequested;
use App\UserFeature\Domain\Event\UserConfirmed;
use App\UserFeature\Domain\Event\UserRegistered;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\Role;
use App\UserFeature\Domain\ValueObject\UserId;
use App\AuditLogFeatureApi\Contract\AuditableInterface;
use App\UserFeature\Domain\ValueObject\UserStatus;

final class User implements AuditableInterface
{
    private string $id;
    private string $email;
    private string $password;
    private UserStatus $status;
    private \DateTimeImmutable $createdAt;
    /** @phpstan-ignore property.unusedType */
    private ?\DateTimeImmutable $deletedAt = null;
    private ?\DateTimeImmutable $passwordUpdatedAt = null;
    // TODO: move to ephemeral store with TTL (Redis / Tarantool).
    private ?string $confirmationCode = null;
    private ?\DateTimeImmutable $codeExpiresAt = null;
    // TODO: move to ephemeral store with TTL (Redis / Tarantool).
    private ?string $passwordResetCode = null;
    private ?\DateTimeImmutable $passwordResetExpiresAt = null;

    /**
     * @var string[]
     */
    private array $roles = [];

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        UserId $id,
        Email $email,
        HashedPassword $password,
        \DateTimeImmutable $createdAt,
        Role ...$roles,
    ) {
        $this->id = $id->value();
        $this->email = $email->value();
        $this->password = $password->value();
        $this->roles = array_map(fn(Role $r) => $r->value, $roles);
        $this->status = UserStatus::ACTIVE;
        $this->createdAt = $createdAt;
    }

    /**
     * Creates an already-active user. Used for reconstituting confirmed
     * accounts; the real sign-up flow goes through {@see self::registerPending()}.
     */
    public static function register(
        UserId $id,
        Email $email,
        HashedPassword $password,
        \DateTimeImmutable $createdAt,
    ): self {
        $user = new self($id, $email, $password, $createdAt, Role::User);
        $user->recordEvent(new UserRegistered($id, $email, ''));

        return $user;
    }

    /**
     * Begins registration: the account stays PENDING until the one-time code
     * is confirmed via {@see self::confirm()}. The code is carried on the
     * UserRegistered event so it can be delivered to the user (e.g. by email).
     */
    public static function registerPending(
        UserId $id,
        Email $email,
        HashedPassword $password,
        string $confirmationCode,
        \DateTimeImmutable $codeExpiresAt,
        \DateTimeImmutable $createdAt,
    ): self {
        $user = new self($id, $email, $password, $createdAt, Role::User);
        $user->status = UserStatus::PENDING;
        $user->confirmationCode = $confirmationCode;
        $user->codeExpiresAt = $codeExpiresAt;
        $user->recordEvent(new UserRegistered($id, $email, $confirmationCode));

        return $user;
    }

    /**
     * Completes registration: validates the one-time code, activates the
     * account and clears the code so it cannot be reused.
     *
     * @throws \DomainException when the user is already confirmed, the code
     *                          does not match, or the code has expired.
     */
    public function confirm(string $code, \DateTimeImmutable $now): void
    {
        if ($this->status === UserStatus::ACTIVE) {
            throw new \DomainException('User is already confirmed');
        }

        if ($this->confirmationCode === null || !hash_equals($this->confirmationCode, $code)) {
            throw new \DomainException('Invalid confirmation code');
        }

        if ($this->codeExpiresAt !== null && $this->codeExpiresAt < $now) {
            throw new \DomainException('Confirmation code has expired');
        }

        $this->status = UserStatus::ACTIVE;
        $this->confirmationCode = null;
        $this->codeExpiresAt = null;
        $this->recordEvent(new UserConfirmed($this->id(), $this->email()));
    }

    /**
     * Replaces the current password with an already-hashed new one and stamps
     * the change time. Verifying that the caller knows the current password is
     * the interactor's responsibility, since the domain never touches plaintext.
     */
    public function changePassword(HashedPassword $newPassword, \DateTimeImmutable $now): void
    {
        $this->password = $newPassword->value();
        $this->passwordUpdatedAt = $now;
        $this->recordEvent(new PasswordChanged($this->id(), $this->email()));
    }

    /**
     * Starts a "forgot password" flow: stores a one-time reset code with an
     * expiry and records {@see PasswordResetRequested} so the code can be
     * delivered to the user (e.g. by email). Any previous code is overwritten.
     */
    public function requestPasswordReset(string $code, \DateTimeImmutable $expiresAt): void
    {
        $this->passwordResetCode = $code;
        $this->passwordResetExpiresAt = $expiresAt;
        $this->recordEvent(new PasswordResetRequested($this->id(), $this->email(), $code));
    }

    /**
     * Completes the "forgot password" flow: validates the one-time code, sets
     * the already-hashed new password and clears the code so it cannot be
     * reused. As with {@see self::changePassword()}, hashing is the caller's
     * responsibility — the domain never touches plaintext.
     *
     * @throws \DomainException when no reset was requested, the code does not
     *                          match, or the code has expired.
     */
    public function resetPassword(string $code, HashedPassword $newPassword, \DateTimeImmutable $now): void
    {
        if ($this->passwordResetCode === null || !hash_equals($this->passwordResetCode, $code)) {
            throw new \DomainException('Invalid reset code');
        }

        if ($this->passwordResetExpiresAt !== null && $this->passwordResetExpiresAt < $now) {
            throw new \DomainException('Reset code has expired');
        }

        $this->password = $newPassword->value();
        $this->passwordUpdatedAt = $now;
        $this->passwordResetCode = null;
        $this->passwordResetExpiresAt = null;
        $this->recordEvent(new PasswordChanged($this->id(), $this->email()));
    }

    public function id(): UserId
    {
        return UserId::fromString($this->id);
    }

    public function email(): Email
    {
        return Email::fromString($this->email);
    }

    public function password(): HashedPassword
    {
        return HashedPassword::fromHash($this->password);
    }

    public function auditTitle(): string
    {
        return $this->email;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function passwordUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->passwordUpdatedAt;
    }

    /**
     * @return string[]
     */
    public function roles(): array
    {
        return $this->roles;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
