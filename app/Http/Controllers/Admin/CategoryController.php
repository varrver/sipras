<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = Category::latest()->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        session()->flash('success', 'Kategori berhasil ditambahkan.');

        return redirect()->route('admin.master.categories.index');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        session()->flash('success', 'Kategori berhasil diperbarui.');

        return redirect()->route('admin.master.categories.index');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        // The complaints FK is restrictOnDelete, so an in-use category would
        // otherwise surface as a raw 500 from the DB constraint. The delete
        // modal promises "masih dipakai pengaduan tidak dapat dihapus" — make
        // the server answer match that promise. The same wall covers the
        // teknisi staffing: deleting a field that a specialist still works
        // would silently close their whole queue, so it refuses for either
        // reason (and the users.category_id FK itself also restricts).
        if ($category->complaints()->exists()) {
            session()->flash('error', 'Kategori tidak dapat dihapus karena masih dipakai pengaduan.');

            return redirect()->route('admin.master.categories.index');
        }

        if ($category->users()->where('role', 'teknisi')->exists()) {
            session()->flash('error', 'Kategori tidak dapat dihapus karena masih ditugaskan ke teknisi.');

            return redirect()->route('admin.master.categories.index');
        }

        $category->delete();

        session()->flash('success', 'Kategori berhasil dihapus.');

        return redirect()->route('admin.master.categories.index');
    }
}
