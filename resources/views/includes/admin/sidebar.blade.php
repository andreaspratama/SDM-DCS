@php

    $user = auth()->user();

    $isAdmin =
        $user
        && method_exists($user, 'isAdmin')
        && $user->isAdmin();


    /*
    |--------------------------------------------------------------------------
    | ACTIVE GROUP
    |--------------------------------------------------------------------------
    */

    $masterOpen = request()->routeIs(
        'employee.*',
        'division.*',
        'employeeOrganization.*',
        'userManagement.*'
    );


    $absensiOpen = request()->routeIs(
        'absensi.*',
        'attendancePermission.*',
        'unitFormToken.*'
    );


    $scheduleOpen = request()->routeIs(
        'workCalendar.*',
        'workSchedule.*',
        'workScheduleAssignment.*',
        'employeeWorkSchedule.*'
    );

@endphp


<!--begin::Sidebar-->
<aside
    class="app-sidebar bg-body-secondary shadow"
    data-bs-theme="dark"
>

    <!--begin::Sidebar Brand-->
    <div class="sidebar-brand">

        <a
            href="{{ route('absensi.index') }}"
            class="brand-link"
        >

            <img
                src="{{ url('./belakang/assets/img/AdminLTELogo.png') }}"
                alt="SDM DCS"
                class="brand-image opacity-75 shadow"
            />

            <span class="brand-text fw-light">
                SDM DCS
            </span>

        </a>

    </div>
    <!--end::Sidebar Brand-->


    <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">

        <nav
            class="mt-2"
            aria-label="Main navigation"
        >

            <ul
                class="nav sidebar-menu flex-column"
                data-lte-toggle="treeview"
                data-accordion="false"
                id="navigation"
            >


                {{-- =====================================================
                    DASHBOARD / BERANDA
                ====================================================== --}}
                <li class="nav-item">

                    <a
                        href="{{ route('dashboard') }}"
                        class="nav-link {{
                            request()->routeIs('dashboard')
                                ? 'active'
                                : ''
                        }}"
                    >

                        <i class="nav-icon bi bi-speedometer2"></i>

                        <p>
                            Dashboard
                        </p>

                    </a>

                </li>


                {{-- =====================================================
                    ABSENSI
                ====================================================== --}}
                <li class="nav-header">
                    ABSENSI
                </li>


                <li class="nav-item {{ $absensiOpen ? 'menu-open' : '' }}">

                    <a
                        href="#"
                        class="nav-link {{ $absensiOpen ? 'active' : '' }}"
                    >

                        <i class="nav-icon bi bi-clipboard-check-fill"></i>

                        <p>

                            Absensi & Izin

                            <i class="nav-arrow bi bi-chevron-right"></i>

                        </p>

                    </a>


                    <ul class="nav nav-treeview">

                        @php
                            $loginEmployeeRole = null;

                            if (
                                auth()->check()
                                && auth()->user()->employee_id
                            ) {
                                $loginEmployeeRole =
                                    \App\Models\Employee::where(
                                        'id',
                                        auth()->user()->employee_id
                                    )->value('role');
                            }
                        @endphp

                        {{-- =====================================================
                            REKAP ABSENSI
                            Kepala Bidang tidak ditampilkan
                        ===================================================== --}}
                        @if($loginEmployeeRole !== 'Kepala Bidang')

                            <li class="nav-item">

                                <a
                                    href="{{ route('absensi.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'absensi.index',
                                            'absensi.datatable',
                                            'absensi.detailRange',
                                            'absensi.export'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-table"></i>

                                    <p>
                                        Rekap Absensi
                                    </p>

                                </a>

                            </li>

                        @endif


                        {{-- UPLOAD ABSENSI --}}
                        @if($isAdmin)

                            <li class="nav-item">

                                <a
                                    href="{{ route('absensi.upload.form') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'absensi.upload.form',
                                            'absensi.upload'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-upload"></i>

                                    <p>
                                        Upload Absensi
                                    </p>

                                </a>

                            </li>

                        @endif


                        {{-- APPROVAL IZIN --}}
                        <li class="nav-item">

                            <a
                                href="{{ route('attendancePermission.index') }}"
                                class="nav-link {{
                                    request()->routeIs(
                                        'attendancePermission.*'
                                    )
                                        ? 'active'
                                        : ''
                                }}"
                            >

                                <i class="nav-icon bi bi-check-circle-fill"></i>

                                <p>
                                    Approval Izin
                                </p>

                            </a>

                        </li>


                        {{-- LINK FORM IZIN --}}
                        @if($isAdmin)

                            <li class="nav-item">

                                <a
                                    href="{{ route('unitFormToken.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'unitFormToken.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-link-45deg"></i>

                                    <p>
                                        Link Form Izin
                                    </p>

                                </a>

                            </li>

                        @endif

                    </ul>

                </li>



                {{-- =====================================================
                    MASTER SDM
                ====================================================== --}}
                @if($isAdmin)

                    <li class="nav-header">
                        MASTER SDM
                    </li>


                    <li class="nav-item {{ $masterOpen ? 'menu-open' : '' }}">

                        <a
                            href="#"
                            class="nav-link {{ $masterOpen ? 'active' : '' }}"
                        >

                            <i class="nav-icon bi bi-people-fill"></i>

                            <p>

                                Data Kepegawaian

                                <i class="nav-arrow bi bi-chevron-right"></i>

                            </p>

                        </a>


                        <ul class="nav nav-treeview">


                            {{-- DATA EMPLOYEE --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('employee.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'employee.index'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-person-lines-fill"></i>

                                    <p>
                                        Data Employee
                                    </p>

                                </a>

                            </li>


                            {{-- UPLOAD EMPLOYEE --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('employee.upload.form') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'employee.upload.form'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-upload"></i>

                                    <p>
                                        Upload Employee
                                    </p>

                                </a>

                            </li>


                            {{-- DIVISI --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('division.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'division.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-grid-fill"></i>

                                    <p>
                                        Bidang / Divisi
                                    </p>

                                </a>

                            </li>


                            {{-- STRUKTUR --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('employeeOrganization.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'employeeOrganization.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-diagram-3-fill"></i>

                                    <p>
                                        Struktur Kepegawaian
                                    </p>

                                </a>

                            </li>


                            {{-- USERS --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('userManagement.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'userManagement.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-person-gear"></i>

                                    <p>
                                        User Management
                                    </p>

                                </a>

                            </li>

                        </ul>

                    </li>



                    {{-- =================================================
                        JADWAL & KALENDER
                    ================================================== --}}
                    <li class="nav-header">
                        JADWAL & KALENDER
                    </li>


                    <li class="nav-item {{ $scheduleOpen ? 'menu-open' : '' }}">

                        <a
                            href="#"
                            class="nav-link {{ $scheduleOpen ? 'active' : '' }}"
                        >

                            <i class="nav-icon bi bi-calendar-week-fill"></i>

                            <p>

                                Jadwal Kerja

                                <i class="nav-arrow bi bi-chevron-right"></i>

                            </p>

                        </a>


                        <ul class="nav nav-treeview">


                            {{-- KALDIK --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('workCalendar.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'workCalendar.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-calendar-date-fill"></i>

                                    <p>
                                        Kalender Kerja / Kaldik
                                    </p>

                                </a>

                            </li>


                            {{-- TEMPLATE --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('workSchedule.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'workSchedule.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-clock-fill"></i>

                                    <p>
                                        Template Jadwal
                                    </p>

                                </a>

                            </li>


                            {{-- PLOTTING --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('workScheduleAssignment.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'workScheduleAssignment.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-people"></i>

                                    <p>
                                        Plotting Jadwal Pegawai
                                    </p>

                                </a>

                            </li>


                            {{-- JADWAL KHUSUS --}}
                            <li class="nav-item">

                                <a
                                    href="{{ route('employeeWorkSchedule.index') }}"
                                    class="nav-link {{
                                        request()->routeIs(
                                            'employeeWorkSchedule.*'
                                        )
                                            ? 'active'
                                            : ''
                                    }}"
                                >

                                    <i class="nav-icon bi bi-calendar2-week"></i>

                                    <p>
                                        Jadwal Khusus Pegawai
                                    </p>

                                </a>

                            </li>

                        </ul>

                    </li>

                @endif


            </ul>

        </nav>

    </div>
    <!--end::Sidebar Wrapper-->

</aside>
<!--end::Sidebar-->



{{-- SIDEBAR LAMA --}}

{{-- <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="./index.html" class="brand-link">
            <!--begin::Brand Image-->
            <img
              src="{{url('./belakang/assets/img/AdminLTELogo.png')}}"
              alt="AdminLTE Logo"
              class="brand-image opacity-75 shadow"
            />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">SDM DCS</span>
            <!--end::Brand Text-->
          </a>
          <!--end::Brand Link-->
        </div>
        <!--end::Sidebar Brand-->
        <!--begin::Sidebar Wrapper-->
        <div class="sidebar-wrapper">
          <nav class="mt-2" aria-label="Main navigation">
            <!--begin::Sidebar Menu-->
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              data-accordion="false"
              id="navigation"
            >
              <li class="nav-item menu-open">
                <a href="#" class="nav-link active">
                  <i class="nav-icon bi bi-speedometer"></i>
                  <p>
                    Dashboard
                  </p>
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-people-fill"></i>
                  <p>
                    Employe
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('employee.upload.form')}}" class="nav-link">
                      
                      <p>Upload</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{route('employee.index')}}" class="nav-link">
                      
                      <p>Data Employee</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-filter-square-fill"></i>
                  <p>
                    Divisi
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('division.index')}}" class="nav-link">
                      
                      <p>Data Divisi</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-people-fill"></i>
                  <p>
                    Struktur Kepegawaian
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('employeeOrganization.index')}}" class="nav-link">
                      
                      <p>Data Struktur Kepegawaian</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-clipboard-fill"></i>
                  <p>
                    Absensi
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('absensi.upload.form')}}" class="nav-link">
                      
                      <p>Upload</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="{{route('absensi.index')}}" class="nav-link">
                      
                      <p>Data Absensi</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-calendar-check-fill"></i>
                  <p>
                    Approval Izin
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('attendancePermission.index')}}" class="nav-link">
                      
                      <p>Data Approval</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-link"></i>
                  <p>
                    Link Token
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('unitFormToken.index')}}" class="nav-link">
                      
                      <p>Data Link</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-calendar-date-fill"></i>
                  <p>
                    Kalender Akademik
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('workCalendar.index')}}" class="nav-link">
                      
                      <p>Data Kaldik</p>
                    </a>
                  </li>
                </ul>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="nav-icon bi bi-person-lines-fill"></i>
                  <p>
                    Users
                    <i class="nav-arrow bi bi-chevron-right"></i>
                  </p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="{{route('userManagement.index')}}" class="nav-link">
                      
                      <p>Data Users</p>
                    </a>
                  </li>
                </ul>
              </li>
            </ul>
            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>
      <!--end::Sidebar--> --}}