<!-- Main sidebar -->
<div class="sidebar sidebar-dark sidebar-main sidebar-expand-lg">

	<!-- Sidebar content -->
	<div class="sidebar-content">

		<!-- Sidebar header -->
		<div class="sidebar-section">
			<div class="sidebar-section-body d-flex justify-content-center">
				<h5 class="sidebar-resize-hide flex-grow-1 my-auto">Navigation</h5>

				<div>
					<button type="button" class="btn btn-flat-white btn-icon btn-sm rounded-pill border-transparent sidebar-control sidebar-main-resize d-none d-lg-inline-flex">
						<i class="ph-arrows-left-right"></i>
					</button>

					<button type="button" class="btn btn-flat-white btn-icon btn-sm rounded-pill border-transparent sidebar-mobile-main-toggle d-lg-none">
						<i class="ph-x"></i>
					</button>
				</div>
			</div>
		</div>
		<!-- /sidebar header -->


		<!-- Main navigation -->
		<div class="sidebar-section">
			<ul class="nav nav-sidebar" data-nav-type="accordion">

				<!-- Main -->
				<li class="nav-item-header pt-0">
					<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Main</div>
					<i class="ph-dots-three sidebar-resize-show"></i>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard') }}" class="nav-link active">
						<i class="ph-house"></i>
						<span>
							Dashboard
							<span class="d-block fw-normal opacity-50">No pending orders</span>
						</span>
					</a>
				</li>
				
				<!--<li class="nav-item">
					<a href="{{ url('/dashboard/leeds') }}" class="nav-link">
						<i class="ph-spinner spinner"></i>
						<span>My Task </span>
						<span class="badge bg-primary align-self-center rounded-pill ms-auto">2</span>
					</a>
				</li>-->
			 
				<!-- Forms -->
				<li class="nav-item-header">
					<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">DULUIN GAJIAN</div>
					<i class="ph-dots-three sidebar-resize-show"></i>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/leads') }}" class="nav-link">
						<i class="ph-user-focus"></i>
						<span>Leads</span>
						 
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/companies') }}" class="nav-link">
						<i class="ph-squares-four"></i>
						<span>Companies</span>
						<!-- <span class="badge bg-primary align-self-center rounded-pill ms-auto">1</span> -->
						<span class="badge bg-primary align-self-center ms-auto notif_companies"></span>
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/employees') }}" class="nav-link">
						<i class="ph-list-numbers"></i>
						<span>Employees</span>
						<span class="badge bg-danger align-self-center ms-auto notif_employees"></span>
					</a>
				</li>
				
				<!-- <li class="nav-item nav-item-submenu">
					<a href="#" class="nav-link">
						<i class="ph-table"></i>
						<span>Loans Project</span>	
					</a>
					<ul class="nav-group-sub collapse">
					<li class="nav-item"><a href="{{ url('/dashboard/loans') }}" class="nav-link">Loans Transaction</a></li>
						<li class="nav-item"><a href="{{ url('/dashboard/loans/companies') }}" class="nav-link">Loans Companies</a></li>
						
					</ul>
				</li> -->
				<li class="nav-item">
					<a href="{{ url('/dashboard/loans') }}" class="nav-link">
						<i class="ph-table"></i>
						<span>Loans Employees</span>
						<span class="badge bg-danger align-self-center ms-auto notif_transaction"></span> 
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/loans/companies') }}" class="nav-link">
						<i class="ph-table"></i>
						<span>Loans Companies</span>
						<span class="badge bg-danger align-self-center ms-auto notif_loan_companies"></span> 
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/report/companies') }}" class="nav-link">
						<i class="ph-table"></i>
						<span>Loans Report</span>
						 
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/invoice') }}" class="nav-link">
						<i class="ph-note-pencil"></i>
						<span>Invoice</span>
						 
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/repayment') }}" class="nav-link">
						<i class="ph-list-numbers"></i>
						<span>Repayment</span>
						 
					</a>
				</li>
				<li class="nav-item-header">
					<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">DULUIN FUNDING</div>
					<i class="ph-dots-three sidebar-resize-show"></i>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/investor') }}" class="nav-link">
						<i class="ph-users"></i>
						<span>Investor</span>
						
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/investor/earn_report') }}" class="nav-link">
						<i class="ph-table"></i>
						<span>Earn Monitoring</span>
						
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/investor/report') }}" class="nav-link">
						<i class="ph-table"></i>
						<span>Stake Report</span>
						
					</a>
				</li>
				<!-- /forms -->

				<!-- Components  
				<li class="nav-item-header">
					<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Landing Page</div>
					<i class="ph-dots-three sidebar-resize-show"></i>
				</li>
				<li class="nav-item nav-item-submenu">
					<a href="#" class="nav-link">
						<i class="ph-text-aa"></i>
						<span>Articles</span>
					</a>
					<ul class="nav-group-sub collapse">
						<li class="nav-item"><a href="components_accordion.html" class="nav-link">List Article</a></li>
						<li class="nav-item"><a href="components_alerts.html" class="nav-link">Create Article</a></li>
					</ul>
				</li>
				<li class="nav-item nav-item-submenu">
					<a href="#" class="nav-link">
						<i class="ph-cards"></i>
						<span>Pages</span>
					</a>
					<ul class="nav-group-sub collapse">
						<li class="nav-item"><a href="components_accordion.html" class="nav-link">List Page</a></li>
						<li class="nav-item"><a href="components_alerts.html" class="nav-link">Create Page</a></li>
					</ul>
				</li>
				<li class="nav-item nav-item-submenu">
					<a href="#" class="nav-link">
						<i class="ph-books"></i>
						<span>Categories</span>
					</a>
					<ul class="nav-group-sub collapse">
						<li class="nav-item"><a href="components_accordion.html" class="nav-link">List Category</a></li>
						<li class="nav-item"><a href="components_alerts.html" class="nav-link">Create Category</a></li>
					</ul>
				</li>
				<li class="nav-item nav-item-submenu">
					<a href="#" class="nav-link">
						<i class="ph-tag"></i>
						<span>Tags</span>
					</a>
					<ul class="nav-group-sub collapse">
						<li class="nav-item"><a href="components_accordion.html" class="nav-link">List Tag</a></li>
						<li class="nav-item"><a href="components_alerts.html" class="nav-link">Create Tag</a></li>
					</ul>
				</li>
				  -->


				<!-- Page kits -->
				<li class="nav-item-header">
					<div class="text-uppercase fs-sm lh-sm opacity-50 sidebar-resize-hide">Global Setting</div>
					<i class="ph-dots-three sidebar-resize-show"></i>
				</li>
				<li class="nav-item">
					<a href="{{ url('/dashboard/users/profile') }}" class="nav-link">
						<i class="ph-sliders"></i>
						<span>Profile</span>
						 
					</a>
				</li>
				<li class="nav-item nav-item-submenu">
					<a href="#" class="nav-link">
						<i class="ph-users-three"></i>
						<span>User Account</span>
					</a>
					<ul class="nav-group-sub collapse">
						<li class="nav-item"><a href="{{ url('dashboard/users/') }}" class="nav-link">User list</a></li>
						<li class="nav-item"><a href="{{ url('dashboard/users/register') }}" class="nav-link">User Add</a></li>
						
					</ul>
				</li>
				<li class="nav-item nav-item-submenu">
					<a href="#" class="nav-link">
						<i class="ph-shield-check"></i>
						<span>User Access Control</span>
					</a>
					<ul class="nav-group-sub collapse">
						<li class="nav-item"><a href="{{ url('dashboard/roles') }}" class="nav-link">User Role</a></li>
						<li class="nav-item"><a href="{{ url('dashboard/permissions') }}" class="nav-link">User Permissions</a></li>
						<li class="nav-item"><a href="{{ url('dashboard/log_activity') }}" class="nav-link">User Activity</a></li>
						 
					</ul>
				</li>
				 
				<li class="nav-item">
					<a href="{{ url('/dashboard/setting') }}" class="nav-link">
						<i class="ph-sliders"></i>
						<span>Web Setting</span>
						 
					</a>
				</li>
				<li class="nav-item">
					<a href="{{ url('/logout') }}" class="nav-link">
						<i class="ph-sign-out"></i>
						<span>Logout</span>
						 
					</a>
				</li>
				<li class="nav-item pb-5">
					
				</li>
				<!-- /page kits -->

			</ul>
		</div>
		<!-- /main navigation -->

	</div>
	<!-- /sidebar content -->
	
</div>
<!-- /main sidebar -->