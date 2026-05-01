<?php

declare(strict_types=1);

use App\Core\Http\HealthController;
use App\Core\Http\Request;
use App\Modules\ACL\Infrastructure\Middleware\PermissionMiddleware;
use App\Modules\Auth\Infrastructure\Middleware\AuthMiddleware;
use App\Modules\Auth\Presentation\AuthController;
use App\Modules\Auth\Presentation\UserAdminController;
use App\Modules\Company\Presentation\CompanyController;
use App\Modules\Files\Presentation\FilesController;
use App\Modules\Person\Presentation\PatientController;
use App\Modules\Person\Presentation\ProfessionalController;
use App\Modules\Person\Presentation\ResponsibleController;
use App\Modules\Person\Presentation\SupplierController;
use App\Modules\ProfessionalPayment\Presentation\PaymentTableController;
use App\Modules\ProfessionalPayment\Presentation\PaymentTableItemController;
use App\Modules\ProfessionalPayment\Presentation\ProfessionalPaymentConfigController;
use App\Modules\Schedule\Presentation\ScheduleEventController;
use App\Modules\Schedule\Presentation\ScheduleEventTypeController;
use App\Modules\Specialty\Presentation\SpecialtyController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$authController = new AuthController();
$userAdminController = new UserAdminController();
$companyController = new CompanyController();
$filesController = new FilesController();
$patientController = new PatientController();
$responsibleController = new ResponsibleController();
$professionalController = new ProfessionalController();
$supplierController = new SupplierController();
$paymentTableController = new PaymentTableController();
$paymentTableItemController = new PaymentTableItemController();
$professionalPaymentConfigController = new ProfessionalPaymentConfigController();
$specialtyController = new SpecialtyController();
$scheduleEventTypeController = new ScheduleEventTypeController();
$scheduleEventController = new ScheduleEventController();
$authMiddleware = new AuthMiddleware();
$permissionMiddleware = new PermissionMiddleware();

$guarded = static function (
    Request $request,
    callable $callback,
    ?string $permission = null
) use (
    $authMiddleware,
    $permissionMiddleware
): array {
    $user = $authMiddleware->handle($request);

    if ($permission !== null) {
        $permissionMiddleware->handle($user, $permission);
    }

    return $callback($request, $user);
};

$routes = new RouteCollection();

$routes->add('healthcheck', new Route('/api/v1/health', [
    '_controller' => new HealthController(),
], [], [], '', [], ['GET']));

$routes->add('auth.login', new Route('/api/v1/auth/login', [
    '_controller' => static fn (Request $request): array => $authController->login($request),
], [], [], '', [], ['POST']));

$routes->add('auth.logout', new Route('/api/v1/auth/logout', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $authController->logout($r)
    ),
], [], [], '', [], ['POST']));

$routes->add('auth.me', new Route('/api/v1/auth/me', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $authController->me($r)
    ),
], [], [], '', [], ['GET']));

$routes->add('auth.change-password', new Route('/api/v1/auth/change-password', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $authController->changePassword($r)
    ),
], [], [], '', [], ['POST']));

$routes->add('company.get', new Route('/api/v1/company', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $companyController->get($r),
        'company.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('company.update', new Route('/api/v1/company', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $companyController->update($r),
        'company.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('files.upload', new Route('/api/v1/files/upload', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $filesController->upload($r),
        'files.upload'
    ),
], [], [], '', [], ['POST']));

$routes->add('files.list', new Route('/api/v1/files', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $filesController->list($r),
        'files.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('files.show', new Route('/api/v1/files/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $filesController->show($r),
        'files.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('files.download', new Route('/api/v1/files/{uuid}/download', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $filesController->download($r),
        'files.download'
    ),
], [], [], '', [], ['GET']));

$routes->add('files.delete', new Route('/api/v1/files/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $filesController->delete($r),
        'files.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('patients.index', new Route('/api/v1/patients', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $patientController->index($r),
        'patients.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('patients.store', new Route('/api/v1/patients', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $patientController->store($r),
        'patients.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('patients.show', new Route('/api/v1/patients/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $patientController->show($r),
        'patients.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('patients.update', new Route('/api/v1/patients/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $patientController->update($r),
        'patients.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('patients.delete', new Route('/api/v1/patients/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $patientController->delete($r),
        'patients.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('responsibles.index', new Route('/api/v1/patients/{uuid}/responsibles', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $responsibleController->index($r),
        'patients.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('responsibles.store', new Route('/api/v1/patients/{uuid}/responsibles', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $responsibleController->store($r),
        'patients.update'
    ),
], [], [], '', [], ['POST']));

$routes->add('responsibles.update', new Route('/api/v1/patient-responsibles/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $responsibleController->update($r),
        'patients.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('responsibles.delete', new Route('/api/v1/patient-responsibles/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $responsibleController->delete($r),
        'patients.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('professionals.index', new Route('/api/v1/professionals', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalController->index($r),
        'professionals.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('professionals.store', new Route('/api/v1/professionals', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalController->store($r),
        'professionals.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('professionals.show', new Route('/api/v1/professionals/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalController->show($r),
        'professionals.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('professionals.update', new Route('/api/v1/professionals/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalController->update($r),
        'professionals.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('professionals.delete', new Route('/api/v1/professionals/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalController->delete($r),
        'professionals.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('professionals.create-user', new Route('/api/v1/professionals/{uuid}/create-user', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalController->createUser($r),
        'professionals.update'
    ),
], [], [], '', [], ['POST']));

$routes->add('suppliers.index', new Route('/api/v1/suppliers', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $supplierController->index($r),
        'suppliers.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('suppliers.store', new Route('/api/v1/suppliers', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $supplierController->store($r),
        'suppliers.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('suppliers.show', new Route('/api/v1/suppliers/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $supplierController->show($r),
        'suppliers.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('suppliers.update', new Route('/api/v1/suppliers/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $supplierController->update($r),
        'suppliers.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('suppliers.delete', new Route('/api/v1/suppliers/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $supplierController->delete($r),
        'suppliers.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('payment-tables.index', new Route('/api/v1/payment-tables', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableController->index($r),
        'professional_payment.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('payment-tables.store', new Route('/api/v1/payment-tables', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableController->store($r),
        'professional_payment.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('payment-tables.show', new Route('/api/v1/payment-tables/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableController->show($r),
        'professional_payment.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('payment-tables.update', new Route('/api/v1/payment-tables/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableController->update($r),
        'professional_payment.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('payment-tables.delete', new Route('/api/v1/payment-tables/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableController->delete($r),
        'professional_payment.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('payment-table-items.index', new Route('/api/v1/payment-tables/{uuid}/items', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableItemController->index($r),
        'professional_payment.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('payment-table-items.store', new Route('/api/v1/payment-tables/{uuid}/items', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableItemController->store($r),
        'professional_payment.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('payment-table-items.update', new Route('/api/v1/payment-table-items/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableItemController->update($r),
        'professional_payment.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('payment-table-items.delete', new Route('/api/v1/payment-table-items/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $paymentTableItemController->delete($r),
        'professional_payment.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('professional-payment-configs.index', new Route('/api/v1/professionals/{uuid}/payment-configs', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalPaymentConfigController->index($r),
        'professional_payment.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('professional-payment-configs.store', new Route('/api/v1/professionals/{uuid}/payment-configs', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalPaymentConfigController->store($r),
        'professional_payment.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('professional-payment-configs.show', new Route('/api/v1/professional-payment-configs/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalPaymentConfigController->show($r),
        'professional_payment.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('professional-payment-configs.update', new Route('/api/v1/professional-payment-configs/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalPaymentConfigController->update($r),
        'professional_payment.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('professional-payment-configs.delete', new Route('/api/v1/professional-payment-configs/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalPaymentConfigController->delete($r),
        'professional_payment.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('professional-payment.resolve-rule', new Route('/api/v1/professionals/{uuid}/payment-rule', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalPaymentConfigController->resolveRule($r),
        'professional_payment.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('professional-payment.simulate', new Route('/api/v1/professionals/{uuid}/simulate-payout', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $professionalPaymentConfigController->simulate($r),
        'professional_payment.simulate'
    ),
], [], [], '', [], ['POST']));

$routes->add('specialties.index', new Route('/api/v1/specialties', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $specialtyController->index($r),
        'professionals.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('specialties.store', new Route('/api/v1/specialties', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $specialtyController->store($r),
        'professionals.update'
    ),
], [], [], '', [], ['POST']));

$routes->add('specialties.update', new Route('/api/v1/specialties/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $specialtyController->update($r),
        'professionals.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('specialties.delete', new Route('/api/v1/specialties/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $specialtyController->delete($r),
        'professionals.update'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('schedule.event-types.index', new Route('/api/v1/schedule/event-types', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventTypeController->index($r),
        'schedule.event_types.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('schedule.event-types.store', new Route('/api/v1/schedule/event-types', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventTypeController->store($r),
        'schedule.event_types.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('schedule.event-types.show', new Route('/api/v1/schedule/event-types/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventTypeController->show($r),
        'schedule.event_types.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('schedule.event-types.update', new Route('/api/v1/schedule/event-types/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventTypeController->update($r),
        'schedule.event_types.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('schedule.event-types.delete', new Route('/api/v1/schedule/event-types/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventTypeController->delete($r),
        'schedule.event_types.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('schedule.events.index', new Route('/api/v1/schedule/events', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->index($r),
        'schedule.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('schedule.events.store', new Route('/api/v1/schedule/events', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->store($r),
        'schedule.create'
    ),
], [], [], '', [], ['POST']));

$routes->add('schedule.events.show', new Route('/api/v1/schedule/events/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->show($r),
        'schedule.view'
    ),
], [], [], '', [], ['GET']));

$routes->add('schedule.events.update', new Route('/api/v1/schedule/events/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->update($r),
        'schedule.update'
    ),
], [], [], '', [], ['PUT']));

$routes->add('schedule.events.delete', new Route('/api/v1/schedule/events/{uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->delete($r),
        'schedule.delete'
    ),
], [], [], '', [], ['DELETE']));

$routes->add('schedule.events.cancel', new Route('/api/v1/schedule/events/{uuid}/cancel', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->cancel($r),
        'schedule.cancel'
    ),
], [], [], '', [], ['POST']));

$routes->add('schedule.events.mark-absence', new Route('/api/v1/schedule/events/{uuid}/mark-absence', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->markAbsence($r),
        'schedule.update'
    ),
], [], [], '', [], ['POST']));

$routes->add('schedule.events.confirm', new Route('/api/v1/schedule/events/{uuid}/confirm', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->confirm($r),
        'schedule.update'
    ),
], [], [], '', [], ['POST']));

$routes->add('schedule.events.mark-done', new Route('/api/v1/schedule/events/{uuid}/mark-done', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->markDone($r),
        'schedule.update'
    ),
], [], [], '', [], ['POST']));

$routes->add('schedule.events.reschedule', new Route('/api/v1/schedule/events/{uuid}/reschedule', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $scheduleEventController->reschedule($r),
        'schedule.update'
    ),
], [], [], '', [], ['POST']));

$routes->add('admin.users.protected', new Route('/api/v1/admin/users', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r, $user): array => $r->method === 'GET'
            ? (function () use ($permissionMiddleware, $user, $userAdminController): array {
                $permissionMiddleware->handle($user, 'users.view');
                return $userAdminController->protectedUsersView();
            })()
            : (function () use ($permissionMiddleware, $user, $userAdminController, $r): array {
                $permissionMiddleware->handle($user, 'users.create');
                return $userAdminController->create($r);
            })()
    ),
], [], [], '', [], ['GET', 'POST']));

$routes->add('admin.users.update', new Route('/api/v1/admin/users/{user_uuid}', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $userAdminController->update($r),
        'users.update'
    ),
], [], [], '', [], ['PATCH']));

$routes->add('admin.users.inactivate', new Route('/api/v1/admin/users/{user_uuid}/inactivate', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $userAdminController->inactivate($r),
        'users.delete'
    ),
], [], [], '', [], ['POST']));

$routes->add('admin.users.roles', new Route('/api/v1/admin/users/{user_uuid}/roles', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $userAdminController->syncRoles($r),
        'acl.manage'
    ),
], [], [], '', [], ['POST']));

$routes->add('admin.users.permissions', new Route('/api/v1/admin/users/{user_uuid}/permissions', [
    '_controller' => static fn (Request $request): array => $guarded(
        $request,
        static fn (Request $r): array => $userAdminController->syncPermissionOverrides($r),
        'acl.manage'
    ),
], [], [], '', [], ['POST']));

return $routes;
