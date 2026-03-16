import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <AuthLayout title="" description="">
            <Head title="Log in" />
            <div className="flex w-full flex-col items-center">
                <div className="w-full max-w-[350px] rounded-xl border border-slate-200 bg-white p-5 shadow-md shadow-slate-600/50">
                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
                        className="flex flex-col gap-5"
                    >
                        {({ processing, errors }) => (
                            <>
                                {(() => {
                                    const credentialError =
                                        'These credentials do not match our records.';
                                    const emailError =
                                        errors.email === 'Email not Existed'
                                            ? 'Email not Existed'
                                            : undefined;
                                    const passwordError =
                                        errors.password === 'Wrong Password' ||
                                        errors.password === credentialError ||
                                        errors.email === credentialError
                                            ? 'Wrong Password'
                                            : undefined;
                                    return (
                                        <div className="grid gap-5">
                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor="email"
                                                    className="text-xs"
                                                >
                                                    Email
                                                </Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="email"
                                                    placeholder="admin@example.com"
                                                    className="border-slate-200 bg-blue-50/80 text-sm placeholder:text-sm focus-visible:ring-[2px] focus-visible:ring-blue-200"
                                                />
                                                <InputError
                                                    message={emailError}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor="password"
                                                    className="text-xs"
                                                >
                                                    Password
                                                </Label>
                                                <Input
                                                    id="password"
                                                    type="password"
                                                    name="password"
                                                    required
                                                    tabIndex={2}
                                                    autoComplete="current-password"
                                                    placeholder="********"
                                                    className="border-slate-200 bg-blue-50/80 text-sm placeholder:text-sm focus-visible:ring-[2px] focus-visible:ring-blue-200"
                                                />
                                                <InputError
                                                    message={passwordError}
                                                />
                                            </div>

                                            <div className="flex items-center gap-3">
                                                <Checkbox
                                                    id="remember"
                                                    name="remember"
                                                    tabIndex={3}
                                                />
                                                <Label htmlFor="remember">
                                                    Remember me
                                                </Label>
                                            </div>

                                            <div className="flex items-center justify-between pt-1">
                                                <div>
                                                    {canResetPassword && (
                                                        <TextLink
                                                            href={request()}
                                                            className="text-sm text-slate-600 underline-offset-4 hover:underline"
                                                            tabIndex={5}
                                                        >
                                                            Forgot your
                                                            password?
                                                        </TextLink>
                                                    )}
                                                </div>
                                                <Button
                                                    type="submit"
                                                    className="bg-blue-600 px-6 text-white transition-colors hover:bg-blue-700 disabled:hover:bg-blue-600"
                                                    tabIndex={4}
                                                    disabled={processing}
                                                    data-test="login-button"
                                                >
                                                    {processing && <Spinner />}
                                                    Log in
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })()}
                            </>
                        )}
                    </Form>
                </div>
            </div>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}
