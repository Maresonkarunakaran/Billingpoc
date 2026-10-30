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
                                <th class="text-center">Products</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $cat->name }}</td>
                                    <td><code>{{ $cat->code ?? '---' }}</code></td>
                                    <td class="text-muted text-sm">{{ $cat->description ?: '---' }}</td>
                                    <td class="text-center font-weight-bold">
                                        <span class="badge badge-light border">{{ $cat->products_count }}</span>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" 
                                                class="btn btn-xs btn-outline-primary btn-edit-category"
                                                data-id="{{ $cat->id }}"
                                                data-name="{{ $cat->name }}"
                                                data-code="{{ $cat->code }}"
                                                data-description="{{ $cat->description }}"
                                                title="Edit Category">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </button>
                                        <form action="{{ route('categories.destroy', $cat->id) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete category \'{{ addslashes($cat->name) }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete Category">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No categories created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT CATEGORY MODAL -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="editCategoryForm" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-edit text-primary mr-1"></i> Edit Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Category Name *</label>
                        <input type="text" name="name" id="edit_cat_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Category Code</label>
                        <input type="text" name="code" id="edit_cat_code" class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-sm">Description</label>
                        <textarea name="description" id="edit_cat_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.btn-edit-category').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = this.getAttribute('data-id');
                var name = this.getAttribute('data-name');
                var code = this.getAttribute('data-code') || '';
                var desc = this.getAttribute('data-description') || '';

                var form = document.getElementById('editCategoryForm');
                form.action = '/categories/' + id;

                document.getElementById('edit_cat_name').value = name;
                document.getElementById('edit_cat_code').value = code;
                document.getElementById('edit_cat_description').value = desc;

                $('#editCategoryModal').modal('show');
            });
        });
    });
</script>
@endpush
@endsection
