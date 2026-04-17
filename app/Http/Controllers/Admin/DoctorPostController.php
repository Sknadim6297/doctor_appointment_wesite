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
        $search = trim((string) $request->query('search'));
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

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
            ->paginate($perPage)
            ->withQueryString();

        $doctors = Enrollment::select('id', 'doctor_name', 'money_rc_no')->orderBy('doctor_name')->get();

        return view('admin.posts.index', compact('posts', 'doctors', 'perPage', 'allowedPerPage', 'search'));
    }

    public function edit(DoctorPost $post)
    {
        return response()->json([
            'success' => true,
            'post' => [
                'id' => $post->id,
                'enrollment_id' => $post->enrollment_id,
                'doctor_name' => $post->doctor_name,
                'post_doc_date' => optional($post->post_doc_date)->format('d/m/Y'),
                'post_doc_consignment_no' => $post->post_doc_consignment_no,
                'post_doc_by' => $post->post_doc_by,
                'post_doc_recieved_date' => optional($post->post_doc_recieved_date)->format('d/m/Y'),
                'post_doc_recieved_by' => $post->post_doc_recieved_by,
                'post_doc_remark' => $post->post_doc_remark,
                'tracking_link' => $post->tracking_link,
            ],
        ]);
    }

    public function store(Request $request)
    {
        return $this->savePostRecord($request, redirectRoute: route('admin.posts'));
    }

    public function storeForDoctor(Request $request, Enrollment $doctor)
    {
        $request->merge(['doctor' => $doctor->id]);

        return $this->savePostRecord(
            $request,
            redirectRoute: route('admin.doctors.show', ['doctor' => $doctor->id, 'tab' => 'doctor_documents'])
        );
    }

    private function savePostRecord(Request $request, string $redirectRoute)
    {
        $data = $request->validate([
            'doctor' => 'required|integer|exists:enrollments,id',
            'doctype' => 'nullable|integer',
            'post_doc_date' => 'nullable|date_format:d/m/Y',
            'post_doc_consignment_no' => 'nullable|string|max:255',
            'post_doc_by' => 'nullable|string|max:255',
            'post_doc_recieved_date' => 'nullable|date_format:d/m/Y',
            'post_doc_recieved_by' => 'nullable|string|max:255',
            'post_doc_remark' => 'required|string|max:255',
            'post_doc_file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $enrollment = Enrollment::findOrFail($data['doctor']);
        $isConsignment = (int) ($data['doctype'] ?? 0) === 7;

        if ($isConsignment) {
            $request->validate([
                'post_doc_date' => 'required|date_format:d/m/Y',
                'post_doc_consignment_no' => 'required|string|max:255',
                'post_doc_by' => 'required|string|max:255',
                'post_doc_recieved_date' => 'required|date_format:d/m/Y',
            ]);
        }

        $filePath = $request->file('post_doc_file')->store('doctor_posts', 'public');

        DoctorPost::create([
            'enrollment_id' => $enrollment->id,
            'doctor_name' => $enrollment->doctor_name,
            'post_doc_date' => $isConsignment ? Carbon::createFromFormat('d/m/Y', $data['post_doc_date'])->format('Y-m-d') : null,
            'post_doc_consignment_no' => $isConsignment ? ($data['post_doc_consignment_no'] ?? null) : null,
            'post_doc_by' => $isConsignment ? ($data['post_doc_by'] ?? null) : null,
            'post_doc_recieved_date' => $isConsignment ? Carbon::createFromFormat('d/m/Y', $data['post_doc_recieved_date'])->format('Y-m-d') : null,
            'post_doc_recieved_by' => $data['post_doc_recieved_by'] ?? null,
            'post_doc_remark' => $data['post_doc_remark'],
            'post_doc_file' => $filePath,
            'created_by' => Auth::id(),
        ]);

        if ($request->filled('return_to')) {
            return redirect()->to($request->input('return_to'))->with('success', 'New post added successfully.');
        }

        return redirect()->to($redirectRoute)->with('success', 'New post added successfully.');
    }

    public function update(Request $request, DoctorPost $post)
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

        $filePath = $post->post_doc_file;
        if ($request->hasFile('post_doc_file')) {
            if ($post->post_doc_file) {
                Storage::disk('public')->delete($post->post_doc_file);
            }

            $filePath = $request->file('post_doc_file')->store('doctor_posts', 'public');
        }

        $post->update([
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
        ]);

        return redirect()->route('admin.posts')->with('success', 'Post updated successfully.');
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
