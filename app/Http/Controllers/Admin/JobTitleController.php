<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobTitleRequest;
use App\Http\Requests\Admin\UpdateJobTitleRequest;
use App\Models\JobTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class JobTitleController extends Controller
{
    public function index(): View
    {
        $jobTitles = JobTitle::withCount('employees')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.job_titles.index', compact('jobTitles'));
    }

    public function store(StoreJobTitleRequest $request): RedirectResponse
    {
        JobTitle::create($request->validated());

        return redirect()->route('admin.job-titles.index')
            ->with('success', 'Jabatan baru berhasil ditambahkan.');
    }

    public function update(UpdateJobTitleRequest $request, JobTitle $jobTitle): RedirectResponse
    {
        $jobTitle->update($request->validated());

        return redirect()->route('admin.job-titles.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function toggleStatus(JobTitle $jobTitle): RedirectResponse
    {
        $jobTitle->update(['is_active' => ! $jobTitle->is_active]);

        $statusText = $jobTitle->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.job-titles.index')
            ->with('success', "Jabatan {$jobTitle->name} berhasil {$statusText}.");
    }

    public function destroy(JobTitle $jobTitle): RedirectResponse
    {
        if ($jobTitle->employees()->count() > 0) {
            return redirect()->route('admin.job-titles.index')
                ->with('error', 'Jabatan tidak dapat dihapus karena masih digunakan oleh karyawan.');
        }

        $jobTitle->delete();

        return redirect()->route('admin.job-titles.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }
}
