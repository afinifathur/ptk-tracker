<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Category, Subcategory};

class CategorySettingsController extends Controller
{
    public function index()
    {
        $cats = Category::with('subcategories:id,category_id,name')->orderBy('name')->get();
        return view('settings.categories', compact('cats'));
    }

    public function storeCategory(Request $r)
    {
        $data = $r->validate(['name'=>'required|string|max:100|unique:categories,name']);
        Category::create($data);
        return back()->with('ok','Kategori ditambahkan.');
    }

    public function updateCategory(Request $r, Category $category)
    {
        $data = $r->validate(['name'=>'required|string|max:100|unique:categories,name,'.$category->id]);
        $category->update($data);
        return back()->with('ok','Kategori diperbarui.');
    }

    public function deleteCategory(Category $category)
    {
        $category->delete(); // akan menghapus subcategories karena FK cascadeOnDelete
        return back()->with('ok','Kategori dihapus.');
    }

    public function storeSubcategory(Request $r)
    {
        $data = $r->validate([
            'category_id'=>'required|exists:categories,id',
            'name'=>'required|string|max:100'
        ]);
        Subcategory::firstOrCreate($data);
        return back()->with('ok','Subkategori ditambahkan.');
    }

    public function updateSubcategory(Request $r, Subcategory $subcategory)
    {
        $data = $r->validate([
            'category_id'=>'required|exists:categories,id',
            'name'=>'required|string|max:100'
        ]);
        // jaga unik per kategori
        $exists = Subcategory::where('category_id',$data['category_id'])
            ->where('name',$data['name'])
            ->where('id','<>',$subcategory->id)->exists();
        if ($exists) return back()->with('ok','Nama subkategori sudah ada di kategori ini.');
        $subcategory->update($data);
        return back()->with('ok','Subkategori diperbarui.');
    }

    public function deleteSubcategory(Subcategory $subcategory)
    {
        $subcategory->delete();
        return back()->with('ok','Subkategori dihapus.');
    }

    // API kecil untuk dropdown dinamis
    public function apiSubcategories(Request $r)
    {
        $r->validate(['category_id'=>'required|integer|exists:categories,id']);
        return Subcategory::where('category_id',$r->category_id)
            ->orderBy('name')->get(['id','name']);
    }
}
