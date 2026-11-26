<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Yönetici Formu'])]
class StaffUserForm extends Component
{
    use \App\Livewire\Admin\Concerns\EditsPublicProfile;
    #[Locked]
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $role_name = 'Yönetici';
    public string $password = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdministrator(), 403);
    }

    public function mount(?int $id = null): void
    {
        if ($id) {
            $u = User::where('is_admin', true)->findOrFail($id);
            $this->userId = $u->id;
            $this->name = $u->name;
            $this->biography = $u->biography ?? '';
            $this->email = $u->email;
            $this->role_name = $u->role_name ?? 'Yönetici';
            $this->password = '';
        } else {
            $this->role_name = 'Yönetici';
        }
    }

    public function rules(): array
    {
        return [
            ...$this->publicProfileRules(),
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . ($this->userId ?? 'NULL'),
            'role_name' => 'required|in:Yönetici,Editör',
            'password' => $this->userId && $this->password === '' ? 'nullable' : 'required|string|min:6',
        ];
    }

    public function save()
    {
        if ($this->userId === auth()->id() && $this->role_name !== 'Yönetici') {
            throw \Illuminate\Validation\ValidationException::withMessages(['role_name' => 'Kendi yöneticilik yetkinizi kaldıramazsınız.']);
        }
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role_name' => $this->role_name,
            'is_admin' => true,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $user = User::where('is_admin', true)->findOrFail($this->userId); $user->update($data); $this->savePublicProfile($user);
            session()->flash('success', 'Yönetici hesabı güncellendi.');
        } else {
            $this->savePublicProfile(User::create($data));
            session()->flash('success', 'Yeni yönetici hesabı oluşturuldu.');
        }

        $this->password = '';

        if (!$this->userId) {
            return redirect()->route('admin.staff-users');
        }
    }

    public function render()
    {
        return view('livewire.admin.staff-user-form');
    }
}
