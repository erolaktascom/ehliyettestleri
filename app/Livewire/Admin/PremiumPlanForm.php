<?php

namespace App\Livewire\Admin;

use App\Models\PremiumPlan;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Premium Plan Formu'])]
class PremiumPlanForm extends Component
{
    public ?int $planId = null;
    public string $name = '';
    public string $short_code = '';
    public int $duration_days = 30;
    public float $price = 99.00;
    public int $order = 0;
    public bool $is_published = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $p = PremiumPlan::findOrFail($id);
            $this->planId = $p->id;
            $this->name = $p->name;
            $this->short_code = $p->short_code;
            $this->duration_days = (int) $p->duration_days;
            $this->price = round($p->price_cents / 100, 2);
            $this->order = (int) $p->order;
            $this->is_published = (bool) $p->is_published;
        } else {
            $this->order = (PremiumPlan::max('order') ?? 0) + 1;
            $this->duration_days = 30;
            $this->price = 99.00;
            $this->is_published = true;
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'short_code' => 'required|string|max:50|unique:premium_plans,short_code,' . ($this->planId ?? 'NULL'),
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'order' => 'required|integer|min:0',
            'is_published' => 'boolean',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'short_code' => $this->short_code,
            'duration_days' => $this->duration_days,
            'price_cents' => (int) round($this->price * 100),
            'order' => $this->order,
            'is_published' => $this->is_published,
        ];

        if ($this->planId) {
            PremiumPlan::findOrFail($this->planId)->update($data);
            session()->flash('success', 'Premium paketi güncellendi.');
        } else {
            PremiumPlan::create($data);
            session()->flash('success', 'Yeni premium paketi eklendi.');
        }

        if (!$this->planId) {
            return redirect()->route('admin.premium-plans');
        }
    }

    public function render()
    {
        return view('livewire.admin.premium-plan-form');
    }
}
