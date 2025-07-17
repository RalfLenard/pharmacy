<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>
<template>
    <AuthBase title="Log in to your account" description="Enter your email and password below to log in">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6 bg-green-50 p-8 rounded-xl shadow-md border border-green-200">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email" class="text-green-800">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                        class="bg-green-100 focus:ring-green-400 focus:border-green-500"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-green-800">Password</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm text-green-600 hover:text-green-800" :tabindex="5">
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Password"
                        class="bg-green-100 focus:ring-green-400 focus:border-green-500"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center space-x-2" :tabindex="3">
                    <Checkbox id="remember" v-model="form.remember" :tabindex="4" />
                    <Label for="remember" class="text-green-800">Remember me</Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full bg-green-500 hover:bg-green-600 text-white transition duration-200"
                    :tabindex="4"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                    Log in
                </Button>
            </div>

            <div class="text-center text-sm text-green-700 mt-4">
                Don't have an account?
                <TextLink :href="route('register')" class="underline underline-offset-4 text-green-600 hover:text-green-800" :tabindex="5">
                    Sign up
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
