<?php

namespace App\Livewire\Admin;

use App\Models\{TrafficSign, TrafficSignCategory};
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\{Layout, Locked};
use Livewire\{Component, WithFileUploads};

#[Layout('layouts.admin')]
class TrafficSignForm extends Component
{
    use WithFileUploads;

    #[Locked] public ?int $signId = null;
    public ?int $traffic_sign_category_id = null;
    public ?string $code = '';
    public string $name = '';
    public string $meaning = '';
    #[Locked] public ?string $image_path = null;
    public $imageUpload;
    public bool $removeImage = false;
    public bool $is_published = false;
    public int $order = 0;

    public function mount(?int $id = null): void
    {
        $this->signId = $id;
        if ($id) {
            $sign = TrafficSign::findOrFail($id);
            foreach (['traffic_sign_category_id', 'code', 'name', 'meaning', 'image_path', 'is_published'] as $field) $this->{$field} = $sign->{$field};
            $this->order = (int) $sign->order;
        } else {
            $this->traffic_sign_category_id = TrafficSignCategory::orderBy('order')->first()?->id;
            $this->order = (TrafficSign::max('order') ?? 0) + 1;
        }
    }

    public function rules(): array
    {
        return [
            'traffic_sign_category_id' => 'required|exists:traffic_sign_categories,id',
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'meaning' => 'required|string|max:10000',
            'imageUpload' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=4096,max_height=4096',
            'removeImage' => 'boolean',
            'is_published' => 'boolean',
            'order' => 'required|integer|min:0',
        ];
    }

    public function updatedImageUpload(): void
    {
        $this->validateOnly('imageUpload');
    }

    public function save()
    {
        $data = $this->validate();
        unset($data['imageUpload'], $data['removeImage']);
        $sign = $this->signId ? TrafficSign::findOrFail($this->signId) : new TrafficSign();
        $oldPath = $sign->image_path;
        $newPath = null;
        try {
            if ($this->imageUpload) {
                $newPath = $this->imageUpload->store('traffic-signs', 'public');
                if (!$newPath) throw new \RuntimeException('Görsel kaydedilemedi.');
                $data['image_path'] = $newPath;
                $data['svg_icon'] = null;
            } elseif ($this->removeImage) {
                $data['image_path'] = null;
                $data['svg_icon'] = null;
            }
            $sign->fill($data)->save();
        } catch (\Throwable $e) {
            if ($newPath) Storage::disk('public')->delete($newPath);
            throw $e;
        }
        if ($oldPath && $oldPath !== $sign->image_path && preg_match('~^traffic-signs/[a-zA-Z0-9_-]+\.(png|jpe?g|webp)$~', $oldPath)
            && !TrafficSign::where('image_path', $oldPath)->exists()) app(\App\Support\MediaLibrary::class)->cleanup($oldPath);
        $this->image_path = $sign->image_path;
        $this->reset('imageUpload', 'removeImage');
        session()->flash('success', $this->signId ? 'Trafik işareti başarıyla güncellendi.' : 'Yeni trafik işareti başarıyla eklendi.');
        if (!$this->signId) return redirect()->route('admin.traffic-signs');
    }

    public function render()
    {
        $categories = TrafficSignCategory::orderBy('order')->get();
        $creatorRecord = $this->signId ? TrafficSign::find($this->signId) : null;
        return view('livewire.admin.traffic-sign-form', compact('categories', 'creatorRecord'));
    }
}
