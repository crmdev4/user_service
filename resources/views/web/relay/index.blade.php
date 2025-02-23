@extends('layouts.dashboard.app')
@section('content')
    <script src="{{ asset('assets/dashboard/js/vendor/tables/datatables/datatables.min.js') }}"></script>
    @include('layouts.dashboard.breadcrumb')

    <!-- Content area -->
    <div class="content">

        <!-- Scrollable datatable -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Relay List</h5>
            </div>

            <div class="card-body d-sm-flex align-items-sm-center justify-content-sm-between flex-sm-wrap">
                <div class="d-flex align-items-center mb-3 mb-sm-0">

                </div>

                <div class="d-flex align-items-center mb-3 mb-sm-0">

                </div>

                <div>
                    <a onClick="create()" href="#" class="btn btn-indigo">
                        <i class="ph-plus me-2"></i>
                        Register Endpoint
                    </a>
                </div>
            </div>


            <table class="table small" id="datatable-relay" width="100%">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Service Name</th>
                        <th>Base Uri</th>
                        <th>Version</th>
                        <th>Method</th>
                        <th>Path</th>
                        <th>Gateway API</th>
                        <th>Relation</th>
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

    <!-- Iconified modal -->
    <div id="formRelay" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-relay" action="#" class="form-horizontal">
                    <input name="id" type="hidden">
                    {{ csrf_field() }}
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ph-list me-2"></i>
                            Relay Form Register
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Service Name</label>
                            <div class="col-sm-9">
                                <input name="service_name" type="text" placeholder="Service name" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Base Uri</label>
                            <div class="col-sm-9">
                                <input name="base_uri" type="text" placeholder="Base Uri" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Versioning</label>
                            <div class="col-lg-3">
                                <div class="input-group">
                                    <span class="input-group-text">V</span>
                                    <input name="version" type="number" class="form-control" value="1" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Method</label>
                            <div class="col-sm-9">
                                <select name="method" placeholder="Method" class="form-control">
                                    <option value="GET">GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PATCH">PATCH</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Path</label>
                            <div class="col-sm-9">
                                <input name="path" type="text" placeholder="api/v1/...." class="form-control">
                                <div class="form-text text-muted">use /{id} is there any request contain uri ID</div>
                            </div>

                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Api Key</label>
                            <div class="col-sm-9">
                                <input name="api_key" type="text" placeholder="API KEY" class="form-control">
                                <div class="form-text text-muted">add service jwt, token or other barear authentication
                                </div>
                            </div>

                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Status</label>
                            <div class="col-sm-9">
                                <select name="status" placeholder="Status" class="form-control">
                                    <option value="0">Disable</option>
                                    <option value="1">Enable</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Reference</label>
                            <div class="col-sm-12">
                                <textarea name="reference" rows="15" class="form-control"></textarea>
                                <div class="form-text text-muted">put any reference here, description or any req body </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <!--<button type="submit" class="btn btn-flat-danger btn-icon">
                                                         <i class="ph-trash"></i>
                                                         Delete
                                                        </button> -->
                        <button class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="ph-check me-1"></i>
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /iconified modal -->

    <!-- Iconified modal -->
    <div id="formRelayRelation" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="form-relay-relation" action="#" class="form-horizontal">
                    <input name="endpoint_register_id" type="hidden">
                    {{ csrf_field() }}
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ph-list me-2"></i>
                            Relay Relation Form Register
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Relay Name</label>
                            <div class="col-sm-9">
                                <input name="relay_name" type="text" placeholder="Relay name" class="form-control"
                                    disabled>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Relay Relation</label>
                            <div class="col-sm-9">
                                <select name="relation_endpoint_register_id" placeholder="Relation to"
                                    class="form-control">

                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Relation Key Id</label>
                            <div class="col-sm-9">
                                <input name="relation_references_name" type="text" placeholder="Relation Key Id"
                                    class="form-control">
                                <div class="form-text text-muted">name of relation id</div>
                            </div>

                        </div>
                        <div class="row mb-3">
                            <label class="col-form-label col-sm-3">Status</label>
                            <div class="col-sm-9">
                                <select name="status" placeholder="Status" class="form-control">
                                    <option value="0">Disable</option>
                                    <option value="1">Enable</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <!--<button type="submit" class="btn btn-flat-danger btn-icon">
                                                         <i class="ph-trash"></i>
                                                         Delete
                                                        </button> -->
                        <button class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="ph-check me-1"></i>
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /iconified modal -->

    <script>
        var data = getUrlVars();
        let method = "POST";

        $.extend($.fn.dataTable.defaults, {
            autoWidth: true,
            responsive: true,
            scrollX: true,
            columnDefs: [{
                orderable: false,
                width: 100,
                targets: [5]
            }],
            buttons: {
                dom: {
                    button: {
                        className: 'btn btn-light'
                    }
                },
                buttons: [{
                        extend: 'copy'
                    },
                    {
                        extend: 'csv'
                    },
                    {
                        extend: 'excel'
                    },
                    {
                        extend: 'pdf'
                    },
                    {
                        extend: 'print'
                    }
                ]
            },
            dom: '<"datatable-header"fl><"datatable-scroll"t><"datatable-footer"ipB>',
            language: {
                search: '<span class="me-3">Filter:</span> <div class="form-control-feedback form-control-feedback-end flex-fill">_INPUT_<div class="form-control-feedback-icon"><i class="ph-magnifying-glass opacity-50"></i></div></div>',
                searchPlaceholder: 'Type to filter...',
                lengthMenu: '<span class="me-3">Show:</span> _MENU_',
                paginate: {
                    'first': 'First',
                    'last': 'Last',
                    'next': document.dir == "rtl" ? '&larr;' : '&rarr;',
                    'previous': document.dir == "rtl" ? '&rarr;' : '&larr;'
                }
            }
        });


        var table = $('#datatable-relay').DataTable({
            "pageLength": 50,
            "scrollY": 400,
            "responsive": true,
            "order": [
                [0, "desc"]
            ],
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": ServUrl + "/relay",
                "data": data,
                "dataSrc": function(json) {
                    json.draw = json.data.draw;
                    json.recordsTotal = json.data.recordsTotal;
                    json.recordsFiltered = json.data.recordsFiltered;
                    console.log(json);
                    return json.data.data;
                },
                "type": "GET",
                "complete": function(response) {

                },
            },
            "columns": [{
                    "data": null
                },
                {
                    "data": "service_name"
                },
                {
                    "data": "base_uri"
                },
                {
                    "data": "version"
                },
                {
                    "data": "method"
                },
                {
                    "data": "path"
                },
                {
                    "data": "masking_url"
                },
                {
                    "data": "count_relation"
                },
                {
                    "data": "status"
                },
                {
                    "data": null
                },
            ],
            "columnDefs": [{
                "targets": -1,
                "data": null,
                "orderable": false,
                "defaultContent": '<div class="dropdown">' +
                    '<a href="#" class="text-body" data-bs-toggle="dropdown"><i class="ph-list"></i></a>' +
                    '<div class="dropdown-menu dropdown-menu-end">' +
                    '<a href="#" id="open" class="dropdown-item"><i class="ph-chart-line me-2"></i>' +
                    'View Statement' +
                    '</a>' +
                    '<a href="#" id="update" class="dropdown-item"><i class="ph-pencil me-2"></i>' +
                    'Update Endpoint' +
                    '</a>' +
                    '<a href="#" id="delete" class="dropdown-item"><i class="ph-lock-key me-2"></i>' +
                    'Delete Endpoint' +
                    '</a>' +
                    '<div class="dropdown-divider"></div>' +
                    '<a href="#" id="relation" class="dropdown-item"><i class="ph-gear me-2"></i>' +
                    'Create Relation' +
                    '</a>' +
                    '<a href="#" id="relation_detail" class="dropdown-item"><i class="ph-magnifying-glass me-2"></i> Show Relation</a>' +
                    '</div>' +
                    '</div>'
            }, {
                "searchable": false,
                "orderable": false,
                "targets": 0,
                "data": "id",
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            }],
            "createdRow": function(row, data, index) {
                $('td', row).eq(-1).addClass('text-center');
                if (data['status'] == 0) {
                    $('td', row).eq(-2).html('<span class="badge bg-warning">Disabled</span>');
                } else {
                    $('td', row).eq(-2).html('<span class="badge bg-success">Enabled</span>');
                }
            }
        });

        $('#datatable-relay tbody').on('click', '#relation', function() {
            var data = table.row($(this).parents('tr')).data();
            getRelation();
            var relay_name = data['service_name'] + ':' + data['base_uri'] + '/' + data['path'];
            $('input[name=endpoint_register_id]').val(data['id']);
            $('input[name=relay_name]').val(relay_name);
            formRelayRelation.show();
        });

        function getRelation() {
            $.ajax({
                url: ServUrl + '/relay/relation/all',
                type: 'GET',
                dataType: 'JSON',
                success: function(response) {

                    let html = '';
                    html += '<option selected disabled>Choose Relation</option>';
                    $.each(response.data, function(k, v) {
                        html += '<option value="' + v.id + '">';
                        html += '['+v.method+']' + v.service_name + ':' + v.base_uri + '/' + v.path;
                        html += '</option>';
                    })
                    $('select[name=relation_endpoint_register_id]').html(html);

                }
            })
        }

        $("select[name=relation_endpoint_register_id]").change(function(val) {
            var idSelected = $(this).val();
            var idrelay = $('input[name=endpoint_register_id]').val();
            if (idSelected == idrelay) {
                swalInit.fire({
                    title: 'Aborted!',
                    text: 'imposible to relation with self relay',
                });
                getRelation();
            }

        });

        $('#datatable-relay tbody').on('click', '#delete', function() {
            var data = table.row($(this).parents('tr')).data();
            var id = data['id'];
            destroy(id);
        });

        $("#datatable-relay tbody").on('click', '#relation_detail', function() {
            var data = table.row($(this).parents('tr')).data();
            var id = data['id'];

            location.href = `{{ url('dashboard/relay/relation') }}/` + id;
        });

        $('#datatable-relay tbody').on('click', '#update', function() {
            var data = table.row($(this).parents('tr')).data();
            $('#formRelay').find('#form-relay')[0].reset();
            formRelay.show();
            method = "PATCH";
            $('input[name=id]').val(data['id']);
            $('input[name=service_name]').val(data['service_name']);
            $('input[name=base_uri]').val(data['base_uri']);
            $('input[name=version]').val(data['version'].replace("v", ""));
            $('select[name=method]').val(data['method']);
            $('input[name=path]').val(data['path']);
            $('input[name=api_key]').val(data['api_key']);
            $('select[name=status]').val(data['status']);
            $('textarea[name=reference]').val(data['reference']);
            $(":submit").removeClass("d-none");
        });

        $('#datatable-relay tbody').on('click', '#open', function() {
            var data = table.row($(this).parents('tr')).data();

            var services_name = data['services_name'];
            $('#formRelay').find('#form-relay')[0].reset();
            formRelay.show();
            $('input[name=id]').val(data['id']);
            $('input[name=service_name]').val(data['service_name']);
            $('input[name=base_uri]').val(data['base_uri']);
            $('input[name=version]').val(data['version'].replace("v", ""));
            $('select[name=method]').val(data['method']);
            $('input[name=path]').val(data['path']);
            $('input[name=api_key]').val(data['api_key']);
            $('select[name=status]').val(data['status']);
            $('textarea[name=reference]').val(data['reference']);
            $(":submit").addClass("d-none");
        });

        var formRelay = new bootstrap.Modal(document.getElementById('formRelay'), {
            keyboard: false,
            backdrop: 'static'
        })

        var formRelayRelation = new bootstrap.Modal(document.getElementById('formRelayRelation'), {
            keyboard: false,
            backdrop: 'static'
        })

        function create() {
            method = "POST";
            formRelay.show();
            $('#formRelay').find('#form-relay')[0].reset();
            $(":submit").removeClass("d-none");
        };

        $('#formRelay').on('shown.bs.modal', function() {
            $(":submit").prop("disabled", false);
        });

        $("#form-relay").submit(function(event) {
            event.preventDefault();
            var data = $(this).serialize();

            swalInit.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'No, cancel!',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                }
            }).then(function(result) {
                if (result.value) {
                    $.ajax({
                        data: data,
                        url: ServUrl + "/relay",
                        crossDomain: false,
                        method: method,
                        complete: function(response) {
                            $(":submit").prop("disabled", false);
                            if (response.status == 201) {
                                swalInit.fire({
                                    title: 'Saved!',
                                    text: response.responseJSON.message,
                                    icon: 'success'
                                });

                                table.ajax.reload();
                            } else {
                                swalInit.fire({
                                    title: 'Aborted!',
                                    text: response.responseJSON.message,
                                    icon: 'warning',
                                });

                                table.ajax.reload();
                            }
                        },
                        dataType: 'json'
                    });

                } else if (result.dismiss === swal.DismissReason.cancel) {
                    table.ajax.reload();
                }
            });

        });

        $("#form-relay-relation").submit(function(event) {
            event.preventDefault();
            var data = $(this).serialize();

            swalInit.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'No, cancel!',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                }
            }).then(function(result) {
                if (result.value) {
                    $.ajax({
                        data: data,
                        url: ServUrl + "/relay/relation",
                        crossDomain: false,
                        method: method,
                        complete: function(response) {
                            $(":submit").prop("disabled", false);
                            if (response.status == 201) {
                                swalInit.fire({
                                    title: 'Saved!',
                                    text: response.responseJSON.message,
                                    icon: 'success'
                                });

                                table.ajax.reload();
                            } else {
                                swalInit.fire({
                                    title: 'Aborted!',
                                    text: response.responseJSON.message,
                                    icon: 'warning',
                                });

                                table.ajax.reload();
                            }
                        },
                        dataType: 'json'
                    });

                } else if (result.dismiss === swal.DismissReason.cancel) {
                    table.ajax.reload();
                }
            });

        });

        function destroy(id) {
            if (id) {
                var path = ServUrl + "/relay";
            }

            swalInit.fire({
                title: 'Are you sure?',
                text: "Are you want to delete this relay",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete!',
                cancelButtonText: 'No, cancel!',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                }
            }).then(function(result) {
                if (result.value) {
                    $.ajax({
                        data: {
                            'id': id
                        },
                        url: path,
                        method: 'DELETE',
                        complete: function(response) {
                            if (response.status == 200) {
                                swalInit.fire({
                                    title: 'saved!',
                                    text: response.responseJSON.message,
                                    icon: 'success'
                                });
                                table.ajax.reload();
                            } else {
                                swalInit.fire({
                                    title: 'Aborted!',
                                    text: response.responseJSON.message,
                                    icon: 'warning'
                                });

                                table.ajax.reload();
                            }
                        },
                        dataType: 'json'
                    });

                } else if (result.dismiss === swal.DismissReason.cancel) {
                    swalInit.fire(
                        'Cancelled',
                        'Your imaginary file is safe :)',
                        'error'
                    );
                }
            });

        };
    </script>
@endsection
