<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorPost;
use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DoctorPostController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $posts = DoctorPost::with(['enrollment', 'creator'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('doctor_name', 'like', "%{$search}%")
                      ->orWhere('post_doc_consignment_no', 'like', "%{$search}%")
                      ->orWhere('post_doc_by', 'like', "%{$search}%")
                      ->orWhere('post_doc_remark', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $doctors = Enrollment::select('id', 'doctor_name', 'money_rc_no')->orderBy('doctor_name')->get();

        return view('admin.posts.index', compact('posts', 'doctors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor' => 'required|integer|exists:enrollments,id',
            'post_doc_date' => 'required|date_format:d/m/Y',
            'post_doc_consignment_no' => 'required|string|max:255',
            'post_doc_by' => 'required|string|max:255',
            'post_doc_recieved_date' => 'required|date_format:d/m/Y',
            'post_doc_recieved_by' => 'nullable|string|max:255',
            'post_doc_remark' => 'required|string|max:255',
            'tracking_link' => 'nullable|url|max:500',
            'post_doc_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $enrollment = Enrollment::findOrFail($data['doctor']);

        $filePath = null;
        if ($request->hasFile('post_doc_file')) {
            $filePath = $request->file('post_doc_file')->store('doctor_posts', 'public');
        }

        DoctorPost::create([
            'enrollment_id' => $enrollment->id,
            'doctor_name' => $enrollment->doctor_name,
            'post_doc_date' => Carbon::createFromFormat('d/m/Y', $data['post_doc_date'])->format('Y-m-d'),
            'post_doc_consignment_no' => $data['post_doc_consignment_no'],
            'post_doc_by' => $data['post_doc_by'],
            'post_doc_recieved_date' => Carbon::createFromFormat('d/m/Y', $data['post_doc_recieved_date'])->format('Y-m-d'),
            'post_doc_recieved_by' => $data['post_doc_recieved_by'] ?? null,
            'post_doc_remark' => $data['post_doc_remark'],
            'tracking_link' => $data['tracking_link'] ?? null,
            'post_doc_file' => $filePath,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.posts')->with('success', 'New post added successfully.');
    }

    public function destroy(DoctorPost $post)
    {
        if ($post->post_doc_file) {
            Storage::disk('public')->delete($post->post_doc_file);
        }

        $post->delete();

        return redirect()->route('admin.posts')->with('success', 'Post deleted successfully.');
    }
}
