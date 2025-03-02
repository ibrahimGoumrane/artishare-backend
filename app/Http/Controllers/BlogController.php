<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show','search']),
        ];
    }

    public function index()
    {
        $blogs = Blog::with(['user', 'categories'])
            ->withCount(['likes', 'comments'])
            ->orderBy('likes_count', 'desc') // Sort by like count in descending order
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Blogs retrieved successfully.',
            'blogs' => $blogs, // Corrected key from 'blog' to 'blogs'
        ], 200);
    }


    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string',
            'currentPage' => 'nullable|integer|min:1',
            'tags' => 'nullable|array', // Validate tags as an array if provided
            'tags.*' => 'nullable|string', // Each tag should be a string
        ]);

        $queryString = $validated['query'] ?? ''; // Default to empty string if no query provided
        $currentPage = $validated['currentPage'] ?? 1; // Default to page 1 if not provided
        $tags = $validated['tags'] ?? []; // Default to empty array if no tags provided

        $blogs = null; // Initialize the result variable for blogs
        $hasMoreBlogs = false; // Initialize flag for more blogs

        // If tags are provided, fetch blogs that match the tags and order by likes count
        if (!empty($tags)) {
            $blogsQuery = Blog::with(['user', 'likes', 'categories', 'comments']) // Add relationships to load, including 'comments'
                ->withCount('likes') // Get the count of likes
                ->whereHas('categories', function ($query) use ($tags) {
                    $query->whereIn('name', $tags);
                })
                ->orderBy('likes_count', 'desc'); // Order by likes count in descending order

            // Fetch blogs and check if there are more
            $blogs = $blogsQuery->take(11)->get(); // Fetch 11 blogs to check for "hasMoreBlogs"
            $hasMoreBlogs = $blogs->count() > 10; // If more than 10 blogs are fetched, there are more blogs
            $blogs = $blogs->take(10); // Limit the result to 10 blogs
        } else {
            // Get blogs that match the search query
            $blogsQuery = Blog::with(['user', 'likes', 'categories', 'comments']) // Add relationships to load, including 'comments'
                ->withCount('likes') // Get the count of likes
                ->orderBy('likes_count', 'desc'); // Order by likes count in descending order

            if ($queryString) {
                $blogsQuery->where('title', 'like', '%' . $queryString . '%');
            }

            $blogs = $blogsQuery->paginate(10, ['*'], 'page', $currentPage); // Paginated query
            $hasMoreBlogs = $blogs->hasMorePages(); // Check if there are more pages
        }

        // Return the results
        return response()->json([
            'currentPage' => !empty($tags) ? 1 : $blogs->currentPage(), // If tags are provided, currentPage will always be 1
            'hasMoreBlogs' => $hasMoreBlogs, // Indicate if there are more blogs
            'blogs' => !empty($tags) ? $blogs->values() : $blogs->items(), // Use items() for paginated results, directly return $blogs for collections
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|unique:blogs,title|max:255',
            'description' => 'nullable|string|max:500',
            'body' => 'required|string',
            'preview' => 'required|string',
            'categories' => 'required|array', // Ensure categories is an array
            'categories.*' => 'required|string|max:255', // Each category must be a string
        ]);

        $user = $request->user();

        $blog = Blog::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'],
            'preview' => $validated['preview'],
            'creator_id' => $user->id,
        ]);

        // Process categories
        $categoryNames = array_map('trim', $validated['categories']); // Trim each category name
        $categoryIds = []; // Store category IDs here

        foreach ($categoryNames as $name) {
            $category = \App\Models\Category::firstOrCreate(['name' => $name]); // Check if category exists, otherwise create
            $categoryIds[] = $category->id; // Collect category ID
        }

        // Sync categories to the blog
        $blog->categories()->sync($categoryIds);

        return response()->json([
            'message' => 'Blog created successfully.',
            'blog' => $blog->load(['user', 'likes', 'categories']), // Load required relationships
        ], 201);
    }
    public function uploadImage(Request $request)
    {
        // Validate the uploaded image
        $validated = $request->validate([
            'blog_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096', // Ensure it's a valid image
        ]);

        // Handle image upload
        if ($request->hasFile('blog_image')) {
            $blogImage = $request->file('blog_image');

            // Generate a unique name for the file
            $blogImageName =  time() . '_' . $blogImage->getClientOriginalName();


            $path = $blogImage->storeAs('uploads/blogs', $blogImageName, 'digitalocean');
            // Save the file to storage
            $blogPhotoPath = Storage::disk('digitalocean')->url($path);
        }

        // Return the updated blog and a success message
        return response()->json([
            'message' => ' saved successfully.',
            'url' => $blogPhotoPath
        ], 200);
    }
    public function show(Blog $blog)
    {
            return response()->json([
                'message' => 'Blog retrieved successfully.',
                'blog' => $blog->load(['user', 'likes.user', 'comments.user', 'categories']),
            ], 200);

    }

    public function update(Request $request, Blog $blog)
    {
        Gate::authorize('update', $blog);

        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:blogs,title,' . $blog->id,
            'description' => 'nullable|string|max:500',
            'body' => 'required|string',
            'preview' => 'required|string',
            'categories' => 'required|array', // Ensure categories field is an array
            'categories.*' => 'required|string|max:255', // Each category must be a string
        ]);

        // Update the blog details
        $blog->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'body' => $validated['body'],
            'preview' => $validated['preview'],
        ]);

        // Process categories
        $categoryNames = array_map('trim', $validated['categories']); // Trim whitespace from each category name
        $categoryIds = []; // Array to hold category IDs

        foreach ($categoryNames as $name) {
            $category = \App\Models\Category::firstOrCreate(['name' => $name]); // Check if a category exists or create a new one
            $categoryIds[] = $category->id; // Collect the category ID
        }

        // Sync the blog's categories
        $blog->categories()->sync($categoryIds); // Ensure only the provided categories are linked

        return response()->json([
            'message' => 'Blog updated successfully.',
            'blog' => $blog->load(['user', 'likes', 'categories']), // Load the necessary relationships
        ], 200);
    }

    public function destroy(Blog $blog)
    {
        try {
            // Authorize user
            Gate::authorize('delete', $blog);
    
            // Delete preview image from DigitalOcean Spaces (if exists)
            if ($blog->preview) {
                $previewPath = str_replace(Storage::disk('digitalocean')->url(''), '', $blog->preview); // Remove base URL
                Storage::disk('digitalocean')->delete($previewPath);
            }
    
            // Delete blog
            $blog->delete();
    
            return response()->json([
                'message' => 'Blog deleted successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('Blog deletion failed: ' . $e->getMessage());
    
            return response()->json([
                'message' => 'An error occurred while deleting the blog.',
            ], 500);
        }
    }


}
