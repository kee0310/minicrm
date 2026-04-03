import { Form } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { destroy } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const confirmInput = useRef<HTMLInputElement>(null);
    const [confirmText, setConfirmText] = useState('');
    const [password, setPassword] = useState('');
    const [clientError, setClientError] = useState<string | null>(null);
    const requiredConfirmText = 'DELETE';

    const isConfirmed =
        confirmText.trim().toUpperCase() === requiredConfirmText;

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Caution"
                description="You will be logged out and cannot recover this account."
            />
            <div className="w-min rounded-lg bg-red-500 p-1 shadow-sm">
                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            Delete account
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="bg-white text-slate-800">
                        <DialogTitle>
                            Are you sure you want to delete your account?
                        </DialogTitle>
                        <DialogDescription>
                            Once your account is deleted, all of its resources
                            and data will also be permanently deleted.
                        </DialogDescription>

                        <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                            Type "DELETE" below to confirm the action.
                        </div>

                        <Form
                            {...destroy.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            onError={() => passwordInput.current?.focus()}
                            onSubmit={(event) => {
                                if (!isConfirmed) {
                                    event.preventDefault();
                                    setClientError(
                                        'Please type DELETE to confirm account deletion.',
                                    );
                                    confirmInput.current?.focus();
                                    return;
                                }

                                if (password.trim() === '') {
                                    event.preventDefault();
                                    setClientError(
                                        'Password is required to delete the account.',
                                    );
                                    passwordInput.current?.focus();
                                    return;
                                }

                                setClientError(null);
                            }}
                            resetOnSuccess
                            className="space-y-6"
                        >
                            {({ resetAndClearErrors, processing, errors }) => (
                                <>
                                    <input
                                        type="text"
                                        name="fakeusernameremembered"
                                        autoComplete="off"
                                        className="hidden"
                                    />
                                    <input
                                        type="password"
                                        name="fakepasswordremembered"
                                        autoComplete="off"
                                        className="hidden"
                                    />
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="confirmText"
                                            className="text-sm font-medium text-slate-700"
                                        >
                                            Confirmation text
                                        </Label>
                                        <Input
                                            id="confirmText"
                                            name="confirmText"
                                            type="text"
                                            value={confirmText}
                                            placeholder="Type DELETE to confirm"
                                            onChange={(event) =>
                                                setConfirmText(
                                                    event.target.value,
                                                )
                                            }
                                            ref={confirmInput}
                                            autoComplete="off"
                                        />
                                        {!isConfirmed && (
                                            <p className="text-xs text-rose-600">
                                                You must type DELETE to enable
                                                account deletion.
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className="sr-only"
                                        >
                                            Password
                                        </Label>

                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            ref={passwordInput}
                                            placeholder="Password"
                                            autoComplete="new-password"
                                            value={password}
                                            onChange={(event) => {
                                                setPassword(event.target.value);
                                                if (clientError) {
                                                    setClientError(null);
                                                }
                                            }}
                                        />

                                        {clientError ? (
                                            <p className="text-xs text-rose-600">
                                                {clientError}
                                            </p>
                                        ) : null}

                                        <InputError message={errors.password} />
                                    </div>

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button
                                                variant="secondary"
                                                onClick={() =>
                                                    resetAndClearErrors()
                                                }
                                            >
                                                Cancel
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            variant="destructive"
                                            type="submit"
                                            disabled={
                                                processing || !isConfirmed
                                            }
                                            data-test="confirm-delete-user-button"
                                            className="bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500"
                                        >
                                            Delete account
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
