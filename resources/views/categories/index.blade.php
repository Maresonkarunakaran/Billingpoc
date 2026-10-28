@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-tags text-primary mr-2"></i>Product Categories</h4>
        <span class="text-muted text-sm">Organize products into hierarchical departments and groups.</span>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="font-weight-bold text-dark mb-0">Create New Category</h6>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Category Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="E.g. Groceries & Staples">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Category Code</label>
                        <input type="text" name="code" class="form-control" placeholder="E.g. GROC">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm">
                        <i class="fas fa-plus mr-1"></i> Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Code</th>
                                <th>Description</th>
                                <th class="text-center">Products Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $cat->name }}</td>
                                    <td><code>{{ $cat->code ?? '---' }}</code></td>
                                    <td class="text-muted text-sm">{{ $cat->description ?: '---' }}</td>
                                    <td class="text-center font-weight-bold"><span class="badge badge-light border">{{ $cat->products_count }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No categories created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
