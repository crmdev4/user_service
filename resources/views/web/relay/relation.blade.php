@extends('layouts.dashboard.app')
@section('content')
    <script src="{{ asset('assets/dashboard/js/vendor/tables/datatables/datatables.min.js') }}"></script>
    @include('layouts.dashboard.breadcrumb')

    <!-- Content area -->
    <div class="content">
        <div id="alert" class="alert alert-danger d-flex align-items-center d-none" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img"
                aria-label="Warning:">
                <path
                    d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
            </svg>
        </div>

        <!-- Scrollable datatable -->
        <div class="card">
            <div class="card-header d-flex align-items-center py-0">
                <h5 class="py-3 mb-0"><i class="ph-list-checks me-2"></i> {{ $data['page_title'] }}</h5>
                <button class="btn btn-indigo ms-auto" onclick="history.back(-1)">
                    <i class="ph-arrow-left me-2"></i> Back
                </button>
            </div>
            <table class="table datatable-column-search-inputs small" id="datatable-relay-relation" width="100%">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Relation to</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
        <!-- /scrollable datatable -->

    </div>
    <!-- /content area -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/PrintArea/2.4.1/jquery.PrintArea.min.js"></script>
    <script>
        $('.sidebar-expand-lg').addClass('sidebar-main-resized');

        var data = getUrlVars();

        // Setting DataTables defaults
        $.extend($.fn.dataTable.defaults, {
            autoWidth: true,
            responsive: true,
            scrollX: true,
            scrollY: 400,
            pageLength: 25,
            columnDefs: [{
                orderable: false,
                width: 100,
                targets: [5]
            }],
            dom: '<"datatable-header"fl><"datatable-scroll"t><"datatable-footer"ip>',
            language: {
                search: '<span class="me-3">Filter:</span> <div class="form-control-feedback form-control-feedback-end flex-fill">_INPUT_<div class="form-control-feedback-icon"><i class="ph-magnifying-glass opacity-50"></i></div></div>',
                searchPlaceholder: 'Type to filter...',
                lengthMenu: '<span class="me-3">Show:</span> _MENU_',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: document.dir === "rtl" ? '&larr;' : '&rarr;',
                    previous: document.dir === "rtl" ? '&rarr;' : '&larr;'
                }
            }
        });

        var table = $('#datatable-relay-relation').DataTable({
            order: [
                [0, "desc"]
            ],
            processing: true,
            serverSide: true,
            ajax: {
                url: ServUrl + `/relay/relation/show/{{ $data['id'] }}`,
                data: function(d) {
                    return $.extend({}, d, data.data); // Combine data from getUrlVars() and DataTables
                },
                dataSrc: function(json) {
                    // Ensure json.data is an array
                    return json.data.data ? json.data.data : [];
                },
                type: "POST",
                complete: function(response) {
                    // Optional: handle complete event
                }
            },
            columns: [{
                    data: null
                }, // For row numbering
                {
                    data: "service_name",
                    render: function(data) {
                        return data.toUpperCase();
                    }
                },
                {
                    data: "relation_references_name"
                },
                {
                    data: "status",
                    render: function(data) {
                        return data === 1 ? '<span class="badge bg-success">Active</span>' :
                            '<span class="badge bg-danger">Inactive</span>';
                    }
                },
                {
                    data: null
                } // For action buttons
            ],
            columnDefs: [{
                targets: -1,
                data: null,
                orderable: false,
                defaultContent: `
            <div class="dropdown">
                <a href="#" class="text-body" data-bs-toggle="dropdown"><i class="ph-list"></i></a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a href="#" id="delete_relation" class="dropdown-item">
                        <i class="ph-trash me-2"></i> Delete
                    </a>
                </div>
            </div>`
            }, {
                searchable: false,
                orderable: false,
                targets: 0,
                data: "id",
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1; // Row numbering
                }
            }],
            createdRow: function(row, data, index) {
                $('td', row).eq(-1).addClass('text-center');
            }
        });

        // Edit modal trigger
        $('#datatable-relay-relation tbody').on('click', '#edit_relation', function() {
            var data = table.row($(this).parents('tr')).data();
            $('#edit_id').val(data.id);
            $('#edit_name').val(data.name);
            $('#edit_code').val(data.code);
            $('#editModal').modal('show');
        });

        // Delete functionality (same as before)
        $('#datatable-relay-relation tbody').on('click', '#delete_relation', function() {
            var data = table.row($(this).parents('tr')).data();
            var id = data.id;
            Swal.fire({
                title: "Are you sure?",
                showCancelButton: true,
                confirmButtonText: "Delete",
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger',
                    confirmedButton: 'btn btn-primary',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: ServUrl + "/relay/relation/{{ $data['id'] }}/delete/" + id,
                        type: "DELETE",
                        success: function(response) {
                            table.row($(this).parents('tr')).remove().draw(false);
                        }
                    });
                }
            });
        });
    </script>
@endsection
