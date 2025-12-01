<?php



namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return view('admin.category.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.category.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create($validated);

        return redirect()->route('admin.category.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        return view('admin.category.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
    ]);

    if ($category->name === $validated['name']) {
        return redirect()
            ->back()
            ->with('unchanged', true);
    }

    $category->update($validated);

    return redirect()
        ->route('admin.category.index')
        ->with('success', 'Category updated successfully.');
}


    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.category.index')->with('success', 'Category deleted successfully.');
    }
}

