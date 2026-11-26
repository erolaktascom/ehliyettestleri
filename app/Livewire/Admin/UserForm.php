<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Kullanıcı Formu'])]
class UserForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsPublicProfile;
    #[Locked]
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $is_admin = false;
    public string $role_name = 'Yönetici';
    public ?string $target_license_class = 'B Sınıfı Ehliyet';
    public ?string $phone = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdministrator(), 403);
    }

    public function mount(?int $id = null): void
    {
        if ($id) {
            $user = User::findOrFail($id);
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->biography = $user->biography ?? '';
            $this->email = $user->email;
            $this->password = '';
            $this->is_admin = (bool) $user->is_admin;
            $this->role_name = $user->is_admin ? $user->role_name : 'Yönetici';
            $this->target_license_class = $user->target_license_class ?? 'B Sınıfı Ehliyet';
            $this->phone = $user->phone ?? '';
        } else {
            $this->target_license_class = 'B Sınıfı Ehliyet';
            $this->is_admin = false;
        }
    }

    public function rules(): array
    {
        return [
            ...$this->publicProfileRules(),
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email,' . ($this->userId ?? 'NULL'),
            'password' => $this->userId && $this->password === '' ? 'nullable' : 'required|string|min:6',
            'is_admin' => 'boolean',
            'role_name' => $this->is_admin ? 'required|in:Yönetici,Editör' : 'nullable',
            'target_license_class' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
        ];
    }

    public function save()
    {
        if ($this->userId === auth()->id() && !$this->is_admin) {
            throw ValidationException::withMessages(['is_admin' => 'Kendi yöneticilik yetkinizi kaldıramazsınız.']);
        }
        if ($this->userId === auth()->id() && $this->role_name !== 'Yönetici') {
            throw ValidationException::withMessages(['role_name' => 'Kendi yöneticilik yetkinizi kaldıramazsınız.']);
        }
        $this->validate();

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->is_admin = $this->is_admin;
            $user->role_name = $this->is_admin ? $this->role_name : 'Öğrenci';
            $user->target_license_class = $this->target_license_class;
            $user->phone = $this->phone;
            if (!empty($this->password)) {
                $user->password = Hash::make($this->password);
            }
            $user->save(); $this->savePublicProfile($user);
            session()->flash('success', 'Kullanıcı başarıyla güncellendi.');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'is_admin' => $this->is_admin,
                'role_name' => $this->is_admin ? $this->role_name : 'Öğrenci',
                'target_license_class' => $this->target_license_class,
                'phone' => $this->phone,
            ]);
            $this->savePublicProfile($user);
            session()->flash('success', 'Yeni kullanıcı başarıyla oluşturuldu.');
        }

        $this->password = '';

        if (!$this->userId) {
            return redirect()->route('admin.users');
        }
    }

    public function render()
    {
        return view('livewire.admin.user-form');
    }
}
