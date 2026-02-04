<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use GuzzleHttp\Handler\Proxy;
use Illuminate\Support\Facades\Storage;

// use Illuminate\Container\Attributes\Storage::disk();
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse;

class ProductController extends Controller
{
    public function index(Request $request) {
        //$products = Product::all(); 
        $query = Product::query();
        if($request->has('search') && $request->search) {
            $query = $query->where("name","like","%".$request->search."%")
                            ->orWhere('description',"like","%".$request->search."%");
        }
        $products = $query->latest()->paginate(8);
        return view('product.product-list',compact('products'));
        
    }
    public function create()
    {
        $categories = Category::all();
      return view('product.create',compact('categories'));
    }
    public function store(Request $request)
    {
  $validated = $request->validate([
    'name' => 'required|string',
        'description' => 'nullable|string',
        'price' => 'required|numeric',
        'quantity' => 'required|numeric',
        'status' => 'required',
        'category_id' => 'required|exists:categories,id', // very important
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

         if ($request->hasFile("image")) {
                $validated["image"] = $request->file("image")->store("products","public");
            }
            Product::create($validated);

            return redirect()->route("product.index")->with("success","product added successfully");
        // dd($validated);

    }
    public function show($id)
    {
     $product = Product::find($id);
     return view("product.show",compact("product"));
    }
      public function edit($id)
    {
        $categories = Category::all();
     $product = Product::find($id);
     return view("product.edit",compact("product","categories",'id'));
    }
  public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string',
        'description' => 'nullable|string',
        'price' => 'required|numeric',
        'quantity' => 'required|numeric',
        'status' => 'required',
        'category_id' => 'required|exists:categories,id',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    if ($request->hasFile('image')) {
        // delete old image
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        // store new image
        $validated['image'] = $request->file('image')->store('products', 'public');
    }

    //UPDATE the already loaded product
    $product->update($validated);

    return redirect()->route('product.index')
                     ->with('success', 'Product updated successfully!');
}
    public function destroy($id){
        Product::find($id)->delete();
        return redirect()->route("product.index")->with("success","product deleted successfully!");
    }
//view for trashed products
public function trashedProducts(Request $request)
{
   // $product = Product::onlyTrashed()->paginate(5);
   $query = Product::query()->onlyTrashed();
        if($request->has('search') && $request->search) {
            $query = $query->where("name","like","%".$request->search."%")
                            ->orWhere('description',"like","%".$request->search."%");
        }
        $products = $query->paginate(5);
    return view('product.deleted-products',compact('products'));
}
 
 
    public function restoreProduct($id)  {
        $product = Product::onlyTrashed()->find($id);
        $product->restore();
        return redirect()->route('product.index')->with('success','product restored successfully');
        
    }
       public function destroyProduct($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        if($product->image && Storage::exists($product->image))
            {
                Storage::delete($product->image);
            }
        $product->forceDelete();
        return redirect()->route('product.index')->with('success','product was deleted successfully');


    }

}
