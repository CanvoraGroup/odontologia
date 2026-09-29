@extends('layouts.admin')

@section('title', 'Inicio - Sistema Odontologico')
@section('page-title', 'Inicio / Dashboard')
@section('page-subtitle', 'Resumen de citas, atenciones, ingresos, deudas y alertas del dia.')
@section('breadcrumb', 'Dashboard')

@section('content')
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3>18</h3>
                    <p>Citas hoy</p>
                </div>
                <i class="small-box-icon bi bi-calendar-check"></i>
                <a href="#" class="small-box-footer">
                    Ver agenda <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>9</h3>
                    <p>Pacientes atendidos</p>
                </div>
                <i class="small-box-icon bi bi-person-check"></i>
                <a href="#" class="small-box-footer">
                    Ver atenciones <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>S/ 1,840</h3>
                    <p>Ingresos del dia</p>
                </div>
                <i class="small-box-icon bi bi-cash-coin"></i>
                <a href="#" class="small-box-footer">
                    Ver caja <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>S/ 4,320</h3>
                    <p>Deuda pendiente</p>
                </div>
                <i class="small-box-icon bi bi-exclamation-circle"></i>
                <a href="#" class="small-box-footer">
                    Ver deudas <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Agenda rapida</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-primary">Hoy</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Paciente</th>
                                    <th>Servicio</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>09:00</td>
                                    <td>Ana Torres</td>
                                    <td>Limpieza dental</td>
                                    <td><span class="badge text-bg-success">Confirmada</span></td>
                                </tr>
                                <tr>
                                    <td>10:30</td>
                                    <td>Carlos Medina</td>
                                    <td>Endodoncia</td>
                                    <td><span class="badge text-bg-warning">En espera</span></td>
                                </tr>
                                <tr>
                                    <td>12:00</td>
                                    <td>Rosa Ramos</td>
                                    <td>Control ortodoncia</td>
                                    <td><span class="badge text-bg-info">Programada</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="#" class="btn btn-odo btn-sm">
                        <i class="bi bi-calendar-plus"></i> Nueva cita
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Alertas</h3>
                    <div class="card-tools">
                        <span class="badge text-bg-warning">4 avisos</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                            <div>
                                <strong>Pago parcial</strong>
                                <div class="text-muted small">Milagros Rojas tiene saldo pendiente.</div>
                            </div>
                            <span class="badge text-bg-warning">S/ 180</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                            <div>
                                <strong>Cita no confirmada</strong>
                                <div class="text-muted small">Paciente programado a las 12:45 p.m.</div>
                            </div>
                            <span class="badge text-bg-info">Llamar</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                            <div>
                                <strong>Historia incompleta</strong>
                                <div class="text-muted small">Paciente nuevo sin antecedentes.</div>
                            </div>
                            <span class="badge text-bg-danger">Revisar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ultimas atenciones</h3>
                    <div class="card-tools">
                        <button class="btn btn-tool" type="button">
                            <i class="bi bi-three-dots"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Servicio</th>
                                    <th>Odontologo</th>
                                    <th>Estado</th>
                                    <th class="text-end">Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Ana Torres</td>
                                    <td>Limpieza dental</td>
                                    <td>Dra. Salazar</td>
                                    <td><span class="badge text-bg-success">Atendida</span></td>
                                    <td class="text-end">S/ 120.00</td>
                                </tr>
                                <tr>
                                    <td>Carlos Medina</td>
                                    <td>Endodoncia</td>
                                    <td>Dra. Salazar</td>
                                    <td><span class="badge text-bg-warning">En proceso</span></td>
                                    <td class="text-end">S/ 350.00</td>
                                </tr>
                                <tr>
                                    <td>Rosa Ramos</td>
                                    <td>Control ortodoncia</td>
                                    <td>Dr. Paredes</td>
                                    <td><span class="badge text-bg-info">Programada</span></td>
                                    <td class="text-end">S/ 180.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
