@extends('layouts.dashboard.app')
@section('content')
 
@include('layouts.dashboard.breadcrumb')

<!-- Content area -->
<div class="content">

	<!-- Scrollable datatable -->
	<div class="card">
		<div class="card-header">
			<h5 class="mb-0">Relay List</h5>
		</div>

		<div class="card-body d-sm-flex align-items-sm-center justify-content-sm-between flex-sm-wrap">
			 <div class="row justify-content-center">
				<div class="col-md-8">
					<div class="card">
						<div class="card-header">Developers Dashboard</div>

						<div class="card-body">
							@if (session('status'))
								<div class="alert alert-success" role="alert">
									{{ session('status') }}
								</div>
							@endif

							<passport-clients></passport-clients>
							<passport-authorized-clients></passport-authorized-clients>
							<passport-personal-access-tokens></passport-personal-access-tokens>
						</div>
					</div>
				</div>
			</div>
		</div>
				 

	</div>
	<!-- /scrollable datatable -->

</div>
<!-- /content area -->

 	
@endsection

