<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Verdict = 'allow' | 'block';

type Sample = {
    id: number;
    prompt: string;
    verdict: Verdict;
    reasons: string[];
};

type Check = {
    id: number;
    prompt: string;
    verdict: Verdict;
    reasons: string[];
    answer: string | null;
    source?: string;
    created_at: string | null;
};

const props = defineProps<{
    samples: Sample[];
    recentChecks: Check[];
    geminiEnabled: boolean;
}>();

const prompt = ref('');
const result = ref<Check | null>(null);
const history = ref<Check[]>(props.recentChecks);
const isChecking = ref(false);
const errorMessage = ref('');
const activeFilter = ref<'all' | Verdict>('all');
const filters = ['all', 'allow', 'block'] as const;

const allowedSamples = computed(() =>
    props.samples.filter((sample) => sample.verdict === 'allow'),
);
const blockedSamples = computed(() =>
    props.samples.filter((sample) => sample.verdict === 'block'),
);
const filteredHistory = computed(() =>
    activeFilter.value === 'all'
        ? history.value
        : history.value.filter((check) => check.verdict === activeFilter.value),
);

function chooseSample(sample: Sample) {
    prompt.value = sample.prompt;
    errorMessage.value = '';
}

async function runCheck() {
    if (isChecking.value || !prompt.value.trim()) return;

    isChecking.value = true;
    errorMessage.value = '';
    result.value = null;

    try {
        const response = await fetch('/checks', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
            },
            body: JSON.stringify({ prompt: prompt.value.trim() }),
        });

        if (!response.ok) {
            if (response.status === 422) {
                errorMessage.value =
                    'Enter a prompt with 2,000 characters or fewer.';
            } else {
                errorMessage.value =
                    'The check could not be completed. Please try again.';
            }
            return;
        }

        const payload = (await response.json()) as { check: Check };
        result.value = payload.check;
        history.value = [payload.check, ...history.value].slice(0, 8);
    } catch {
        errorMessage.value =
            'Network error. Check your connection and try again.';
    } finally {
        isChecking.value = false;
    }
}

function formatTime(value: string | null) {
    if (!value) return 'Just now';
    return new Intl.DateTimeFormat('en', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Prompt safety console" />

    <div class="min-h-screen bg-[#f6f7f9] text-[#182230]">
        <header class="border-b border-[#e8ebef] bg-white">
            <div
                class="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between px-5 sm:px-8"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="Policy Guard Lab home"
                >
                    <span
                        class="grid size-10 place-items-center rounded-xl bg-[#173f35] text-white shadow-sm"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="size-[22px]"
                            aria-hidden="true"
                        >
                            <path
                                d="M12 3 19 6v5c0 4.5-2.9 8.1-7 10-4.1-1.9-7-5.5-7-10V6l7-3Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                            <path
                                d="m9 12 2 2 4-4"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </span>
                    <span>
                        <span
                            class="block text-[15px] font-semibold tracking-[-0.02em]"
                            >Policy Guard</span
                        >
                        <span
                            class="block text-[11px] font-medium tracking-[0.08em] text-[#7d8794] uppercase"
                            >Harbor Desk · AI safety lab</span
                        >
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden items-center gap-2 rounded-full border border-[#e5e9e7] bg-[#fbfcfb] px-3 py-1.5 text-xs font-medium text-[#52615b] sm:flex"
                    >
                        <span class="size-1.5 rounded-full bg-[#42a779]"></span>
                        {{
                            geminiEnabled
                                ? 'Gemini connected'
                                : 'Local mode active'
                        }}
                    </span>
                    <a
                        href="#how-it-works"
                        class="hidden text-sm font-medium text-[#64707e] transition hover:text-[#173f35] sm:block"
                        >How it works</a
                    >
                    <span
                        class="grid size-9 place-items-center rounded-full border border-[#e4e8ed] bg-[#f5f7f8] text-xs font-semibold text-[#60716a]"
                        aria-label="Guest workspace"
                        >PG</span
                    >
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-5 pt-9 pb-16 sm:px-8 lg:pt-12">
            <section
                class="mb-8 flex flex-col justify-between gap-5 lg:flex-row lg:items-end"
            >
                <div class="max-w-2xl">
                    <div
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-[0.13em] text-[#718078] uppercase"
                    >
                        <span class="size-1.5 rounded-full bg-[#56a785]"></span>
                        Prompt inspection workspace
                    </div>
                    <h1
                        class="text-[32px] leading-tight font-semibold tracking-[-0.045em] text-[#1c2b26] sm:text-[38px]"
                    >
                        A safer answer starts here.
                    </h1>
                    <p
                        class="mt-2 max-w-xl text-[15px] leading-6 text-[#6d7884]"
                    >
                        Inspect a prompt before it reaches the assistant. Clear
                        policy rules catch known attacks; only allowed prompts
                        continue to Harbor Desk support.
                    </p>
                </div>
                <div
                    class="flex items-center gap-4 rounded-xl border border-[#e5e9e7] bg-white px-4 py-3 shadow-[0_1px_2px_rgba(20,35,30,0.02)]"
                >
                    <span
                        class="grid size-9 place-items-center rounded-lg bg-[#edf5f1] text-[#357a5f]"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="size-5"
                            aria-hidden="true"
                        >
                            <path
                                d="m12 3 7 3v5c0 4.5-2.9 8.1-7 10-4.1-1.9-7-5.5-7-10V6l7-3Z"
                                stroke="currentColor"
                                stroke-width="1.6"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M9 12h6M12 9v6"
                                stroke="currentColor"
                                stroke-width="1.6"
                                stroke-linecap="round"
                            />
                        </svg>
                    </span>
                    <div>
                        <p class="text-[13px] font-semibold text-[#283b33]">
                            Guard is ready
                        </p>
                        <p class="mt-0.5 text-[11px] text-[#829087]">
                            Rules run before any model call
                        </p>
                    </div>
                </div>
            </section>

            <div
                class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.25fr)_minmax(360px,0.75fr)]"
            >
                <section
                    class="overflow-hidden rounded-2xl border border-[#e4e8eb] bg-white shadow-[0_3px_14px_rgba(20,35,30,0.035)]"
                >
                    <div
                        class="flex items-center justify-between border-b border-[#edf0f1] px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="grid size-8 place-items-center rounded-lg bg-[#f0f4f2] text-[#49665b]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M8 5H5v14h14v-3M10 14 20 4m-6 0h6v6"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            <div>
                                <h2
                                    class="text-sm font-semibold text-[#26352f]"
                                >
                                    Inspect a prompt
                                </h2>
                                <p class="mt-0.5 text-[11px] text-[#87918b]">
                                    Try an example or write your own
                                </p>
                            </div>
                        </div>
                        <span
                            class="rounded-md border border-[#e9ecee] px-2 py-1 font-mono text-[10px] text-[#8b959c]"
                            >POST /checks</span
                        >
                    </div>

                    <div class="p-5 sm:p-6">
                        <div
                            class="mb-5 flex flex-wrap gap-1 rounded-lg bg-[#f3f5f5] p-1"
                        >
                            <span
                                class="basis-full px-2 pt-1.5 pb-1 text-[10px] font-semibold tracking-[0.1em] text-[#8a9590] uppercase"
                                >Safe examples</span
                            >
                            <button
                                v-for="sample in allowedSamples"
                                :key="sample.id"
                                type="button"
                                class="sample-chip"
                                @click="chooseSample(sample)"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-[#5baf83]"
                                ></span
                                >{{ sample.prompt }}
                            </button>
                            <span
                                class="basis-full px-2 pt-3 pb-1 text-[10px] font-semibold tracking-[0.1em] text-[#8a9590] uppercase"
                                >Injection attempts</span
                            >
                            <button
                                v-for="sample in blockedSamples"
                                :key="sample.id"
                                type="button"
                                class="sample-chip sample-chip--blocked"
                                @click="chooseSample(sample)"
                            >
                                <span
                                    class="size-1.5 rounded-full bg-[#d3786e]"
                                ></span
                                >{{ sample.prompt }}
                            </button>
                        </div>

                        <form @submit.prevent="runCheck">
                            <div class="mb-2 flex items-center justify-between">
                                <label
                                    for="prompt"
                                    class="text-xs font-semibold text-[#435149]"
                                    >Prompt to inspect</label
                                >
                                <span class="text-[11px] text-[#97a09c]"
                                    >{{ prompt.length }} / 2,000</span
                                >
                            </div>
                            <textarea
                                id="prompt"
                                v-model="prompt"
                                rows="5"
                                maxlength="2000"
                                placeholder="Ask a Harbor Desk question, or test a prompt injection…"
                                class="w-full resize-y rounded-xl border border-[#dfe5e2] bg-[#fcfdfc] px-4 py-3.5 text-[14px] leading-6 text-[#24342d] transition outline-none placeholder:text-[#a0aaa5] focus:border-[#72a58d] focus:ring-4 focus:ring-[#317a5c]/[0.08]"
                            />
                            <p
                                v-if="errorMessage"
                                role="alert"
                                class="mt-2 text-xs font-medium text-[#b64238]"
                            >
                                {{ errorMessage }}
                            </p>
                            <div
                                class="mt-4 flex flex-col-reverse items-start justify-between gap-3 sm:flex-row sm:items-center"
                            >
                                <p
                                    class="max-w-sm text-[11px] leading-5 text-[#929c96]"
                                >
                                    Blocked prompts are stored for this demo,
                                    but never sent to the answer model.
                                </p>
                                <button
                                    type="submit"
                                    :disabled="isChecking || !prompt.trim()"
                                    class="inline-flex min-w-[156px] items-center justify-center gap-2 rounded-lg bg-[#174b3b] px-4 py-2.5 text-[13px] font-semibold text-white shadow-[0_2px_4px_rgba(23,75,59,0.16)] transition hover:bg-[#123d30] disabled:cursor-not-allowed disabled:opacity-45"
                                >
                                    <svg
                                        v-if="isChecking"
                                        class="size-4 animate-spin"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <circle
                                            cx="12"
                                            cy="12"
                                            r="9"
                                            stroke="currentColor"
                                            stroke-opacity=".25"
                                            stroke-width="3"
                                        />
                                        <path
                                            d="M21 12a9 9 0 0 0-9-9"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        class="size-4"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1"
                                            stroke="currentColor"
                                            stroke-width="1.7"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                    {{
                                        isChecking
                                            ? 'Checking prompt…'
                                            : 'Run safety check'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-2xl border border-[#e4e8eb] bg-white shadow-[0_3px_14px_rgba(20,35,30,0.035)]"
                    aria-live="polite"
                >
                    <div
                        class="flex items-center justify-between border-b border-[#edf0f1] px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="grid size-8 place-items-center rounded-lg bg-[#f0f4f2] text-[#49665b]"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-4"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M4 5h16v11H8l-4 4V5Z"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M8 9h8m-8 3h5"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>
                            <div>
                                <h2
                                    class="text-sm font-semibold text-[#26352f]"
                                >
                                    Inspection result
                                </h2>
                                <p class="mt-0.5 text-[11px] text-[#87918b]">
                                    Verdict and downstream response
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="result"
                            class="text-[10px] text-[#9aa39e]"
                            >{{ formatTime(result.created_at) }}</span
                        >
                    </div>

                    <div
                        v-if="!result"
                        class="flex min-h-[340px] flex-col items-center justify-center px-8 py-12 text-center"
                    >
                        <span
                            class="mb-4 grid size-[58px] place-items-center rounded-2xl border border-[#e8eeea] bg-[#f7faf8] text-[#67917e]"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                class="size-7"
                                aria-hidden="true"
                            >
                                <path
                                    d="M12 3 19 6v5c0 4.5-2.9 8.1-7 10-4.1-1.9-7-5.5-7-10V6l7-3Z"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M9 12h6"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                        <h3 class="text-sm font-semibold text-[#415148]">
                            Your result will appear here
                        </h3>
                        <p
                            class="mt-1.5 max-w-[260px] text-xs leading-5 text-[#929c96]"
                        >
                            The guard checks the prompt first. An answer is
                            generated only after it passes.
                        </p>
                        <div
                            class="mt-7 grid w-full max-w-[300px] grid-cols-2 gap-2 text-left"
                        >
                            <div
                                class="rounded-lg border border-[#edf0ee] px-3 py-2.5"
                            >
                                <p
                                    class="text-[10px] font-semibold tracking-wide text-[#9aa39e] uppercase"
                                >
                                    Policy rules
                                </p>
                                <p
                                    class="mt-1 text-[11px] font-medium text-[#53635a]"
                                >
                                    Always first
                                </p>
                            </div>
                            <div
                                class="rounded-lg border border-[#edf0ee] px-3 py-2.5"
                            >
                                <p
                                    class="text-[10px] font-semibold tracking-wide text-[#9aa39e] uppercase"
                                >
                                    Answer model
                                </p>
                                <p
                                    class="mt-1 text-[11px] font-medium text-[#53635a]"
                                >
                                    Allowed only
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span
                                    v-if="result.verdict === 'allow'"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-[#cbe8d8] bg-[#eef8f2] px-2.5 py-1 text-[11px] font-semibold text-[#28734e]"
                                    ><span
                                        class="size-1.5 rounded-full bg-[#49a875]"
                                    ></span
                                    >Allowed</span
                                >
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full border border-[#f0d0cc] bg-[#fff3f1] px-2.5 py-1 text-[11px] font-semibold text-[#ad473c]"
                                    ><span
                                        class="size-1.5 rounded-full bg-[#cf695f]"
                                    ></span
                                    >Blocked</span
                                >
                                <p
                                    class="mt-3 text-xs leading-5 text-[#748078]"
                                >
                                    {{
                                        result.verdict === 'allow'
                                            ? 'This prompt passed the guard and was sent to Harbor Desk support.'
                                            : 'This prompt was stopped before reaching the answer model.'
                                    }}
                                </p>
                            </div>
                            <span
                                class="rounded-md bg-[#f5f6f6] px-2 py-1 font-mono text-[10px] text-[#86918b]"
                                >{{
                                    result.source === 'policy_rules'
                                        ? 'RULE MATCH'
                                        : result.source === 'llm_classifier'
                                          ? 'MODEL REVIEW'
                                          : 'RULES ONLY'
                                }}</span
                            >
                        </div>

                        <div
                            v-if="result.reasons.length"
                            class="mt-5 rounded-xl border border-[#f0dfdc] bg-[#fffaf9] p-4"
                        >
                            <p
                                class="mb-2 text-[10px] font-semibold tracking-[0.1em] text-[#ad6259] uppercase"
                            >
                                Why it was blocked
                            </p>
                            <ul class="space-y-2">
                                <li
                                    v-for="reason in result.reasons"
                                    :key="reason"
                                    class="flex gap-2 text-xs leading-5 text-[#76514d]"
                                >
                                    <span
                                        class="mt-[7px] size-1 shrink-0 rounded-full bg-[#cc766d]"
                                    ></span
                                    >{{ reason }}
                                </li>
                            </ul>
                        </div>

                        <div
                            v-if="result.answer"
                            class="mt-5 rounded-xl border border-[#e7ece9] bg-[#f9fbfa] p-4"
                        >
                            <div
                                class="mb-2 flex items-center gap-2 text-[10px] font-semibold tracking-[0.1em] text-[#77877e] uppercase"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-3.5 text-[#4b8b6d]"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                Harbor Desk answer
                            </div>
                            <p class="text-[13px] leading-6 text-[#46564d]">
                                {{ result.answer }}
                            </p>
                        </div>

                        <div class="mt-5 rounded-lg bg-[#f6f7f7] px-3.5 py-3">
                            <p
                                class="text-[10px] font-semibold tracking-wide text-[#9aa39e] uppercase"
                            >
                                Inspected prompt
                            </p>
                            <p
                                class="mt-1.5 line-clamp-3 text-xs leading-5 break-words text-[#637069]"
                            >
                                {{ result.prompt }}
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <section
                id="how-it-works"
                class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
            >
                <div
                    class="rounded-2xl border border-[#e4e8eb] bg-white px-5 py-5 shadow-[0_3px_14px_rgba(20,35,30,0.025)] sm:px-6"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[#26352f]">
                                Guard pipeline
                            </h2>
                            <p class="mt-1 text-[11px] text-[#87918b]">
                                Deterministic checks before optional AI
                            </p>
                        </div>
                        <span class="font-mono text-[10px] text-[#a0aaa4]"
                            >12s max model timeout</span
                        >
                    </div>
                    <div
                        class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center"
                    >
                        <div class="pipeline-node">
                            <span
                                class="grid size-7 place-items-center rounded-md bg-[#f0f3f1] text-[#62766a]"
                                ><svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-4"
                                >
                                    <path
                                        d="M5 7h14M5 12h14M5 17h9"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                    /></svg></span
                            ><span
                                ><b>Prompt</b
                                ><small>Untrusted input</small></span
                            >
                        </div>
                        <span class="pipeline-arrow">→</span>
                        <div class="pipeline-node">
                            <span
                                class="grid size-7 place-items-center rounded-md bg-[#edf5f1] text-[#438064]"
                                ><svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    class="size-4"
                                >
                                    <path
                                        d="m12 3 7 3v5c0 4.5-2.9 8.1-7 10-4.1-1.9-7-5.5-7-10V6l7-3Z"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    />
                                    <path
                                        d="M9 12h6"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    /></svg></span
                            ><span
                                ><b>Policy rules</b
                                ><small
                                    >Override · leak · jailbreak</small
                                ></span
                            >
                        </div>
                        <span class="pipeline-arrow">→</span>
                        <div class="pipeline-branches">
                            <span class="branch-pill branch-pill--block"
                                >Block · explain</span
                            ><span class="branch-pill branch-pill--allow"
                                >Allow → answer</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-[#e4e8eb] bg-white px-5 py-5 shadow-[0_3px_14px_rgba(20,35,30,0.025)] sm:px-6"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-semibold text-[#26352f]">
                                Recent checks
                            </h2>
                            <p class="mt-1 text-[11px] text-[#87918b]">
                                Latest prompts inspected in this session
                            </p>
                        </div>
                        <div class="flex rounded-lg bg-[#f3f5f4] p-0.5">
                            <button
                                v-for="filter in filters"
                                :key="filter"
                                type="button"
                                class="filter-button"
                                :class="{
                                    'filter-button--active':
                                        activeFilter === filter,
                                }"
                                @click="activeFilter = filter"
                            >
                                {{
                                    filter === 'all'
                                        ? 'All'
                                        : filter === 'allow'
                                          ? 'Allowed'
                                          : 'Blocked'
                                }}
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="filteredHistory.length"
                        class="divide-y divide-[#f0f2f1]"
                    >
                        <button
                            v-for="check in filteredHistory"
                            :key="check.id"
                            type="button"
                            class="history-row"
                            @click="
                                result = check;
                                prompt = check.prompt;
                            "
                        >
                            <span
                                class="size-2 shrink-0 rounded-full"
                                :class="
                                    check.verdict === 'allow'
                                        ? 'bg-[#54a879]'
                                        : 'bg-[#ce7067]'
                                "
                            ></span>
                            <span
                                class="min-w-0 flex-1 truncate text-xs text-[#5f6d65]"
                                >{{ check.prompt }}</span
                            >
                            <span
                                class="shrink-0 text-[10px] font-semibold uppercase"
                                :class="
                                    check.verdict === 'allow'
                                        ? 'text-[#39805b]'
                                        : 'text-[#b85b52]'
                                "
                                >{{ check.verdict }}</span
                            >
                            <span
                                class="hidden shrink-0 text-[10px] text-[#a3aca7] sm:block"
                                >{{ formatTime(check.created_at) }}</span
                            >
                        </button>
                    </div>
                    <div
                        v-else
                        class="grid min-h-[78px] place-items-center rounded-lg border border-dashed border-[#e7ebe8] text-xs text-[#9aa39e]"
                    >
                        No checks yet. Run your first prompt above.
                    </div>
                </div>
            </section>

            <footer
                class="mt-7 flex flex-col items-start justify-between gap-2 border-t border-[#e6e9eb] pt-5 text-[10px] text-[#99a29d] sm:flex-row sm:items-center"
            >
                <p>
                    Policy Guard Lab · A prompt-injection safety demo, not a
                    production security boundary.
                </p>
                <p class="font-mono">
                    DETERMINISTIC FIRST · FAIL-SOFT · NO SECRETS REQUIRED
                </p>
            </footer>
        </main>
    </div>
</template>

<style scoped>
.sample-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid #e2e9e4;
    border-radius: 7px;
    background: #fff;
    padding: 7px 9px;
    color: #496457;
    font-size: 10px;
    line-height: 1.3;
    text-align: left;
    transition:
        border-color 140ms ease,
        background 140ms ease;
}
.sample-chip:hover {
    border-color: #9cbbaa;
    background: #f8fbf9;
}
.sample-chip--blocked {
    border-color: #eee3e1;
    color: #795a56;
}
.sample-chip--blocked:hover {
    border-color: #d7aaa4;
    background: #fffaf9;
}
.pipeline-node {
    display: flex;
    min-width: 0;
    flex: 1;
    align-items: center;
    gap: 8px;
    border: 1px solid #e9edeb;
    border-radius: 9px;
    padding: 8px;
}
.pipeline-node b {
    display: block;
    color: #4d5d54;
    font-size: 10px;
    font-weight: 650;
}
.pipeline-node small {
    display: block;
    margin-top: 2px;
    color: #9aa39e;
    font-size: 9px;
    white-space: nowrap;
}
.pipeline-arrow {
    color: #b7c0ba;
    text-align: center;
}
.pipeline-branches {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 5px;
}
.branch-pill {
    border-radius: 6px;
    padding: 6px 8px;
    font-size: 9px;
    font-weight: 650;
}
.branch-pill--block {
    background: #fff4f2;
    color: #ae5b52;
}
.branch-pill--allow {
    background: #eff8f2;
    color: #43815e;
}
.filter-button {
    border-radius: 6px;
    padding: 5px 8px;
    color: #89948d;
    font-size: 9px;
    font-weight: 600;
    transition: all 140ms ease;
}
.filter-button--active {
    background: white;
    color: #40564a;
    box-shadow: 0 1px 3px #1a33230d;
}
.history-row {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 9px;
    padding: 10px 2px;
    text-align: left;
}
.history-row:hover .truncate {
    color: #285b45;
}
@media (max-width: 639px) {
    .pipeline-arrow {
        transform: rotate(90deg);
    }
    .pipeline-node small {
        white-space: normal;
    }
}
</style>
