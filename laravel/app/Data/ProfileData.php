<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Concerns\RemembersWhatWasSent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

readonly class ProfileData
{
    use RemembersWhatWasSent;

    /**
     * @param  array<string, bool>  $settings
     * @param  array<int, string>  $sent
     */
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $language = null,
        public ?string $timezone = null,
        public array $settings = [],
        public ?UploadedFile $photo = null,
        private array $sent = [],
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? null,
            email: $validated['email'] ?? null,
            password: $validated['password'] ?? null,
            language: $validated['language'] ?? null,
            timezone: $validated['timezone'] ?? null,
            settings: $validated['settings'] ?? [],
            photo: $request->file('photo'),
            sent: array_keys($validated),
        );
    }

    /**
     * The model hashes the password on the way in.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return $this->only([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'language' => $this->language,
            'timezone' => $this->timezone,
        ]);
    }
}
