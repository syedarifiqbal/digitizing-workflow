<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import Button from "@/Components/Button.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import OrderTimeline from "@/Components/OrderTimeline.vue";
import { useDateFormat } from "@/Composables/useDateFormat";

const { formatDate } = useDateFormat();

const props = defineProps({
    order: Object,
    inputFiles: Array,
    outputFiles: Array,
    canAssign: Boolean,
    designers: Array,
    salesUsers: Array,
    allowedTransitions: Array,
    canCreateRevision: Boolean,
    canDeliver: Boolean,
    alreadyDelivered: Boolean,
    canCancel: Boolean,
    revisionOrders: Array,
    canSubmitWork: Boolean,
    alreadySubmitted: Boolean,
    maxUploadMb: Number,
    allowedOutputExtensions: String,
    timeline: Array,
    enableDesignerTips: Boolean,
    currency: String,
    commissions: Array,
    comments: Array,
    invoiceInfo: Object,
    downloadInputZipUrl: String,
    downloadOutputZipUrl: String,
    deliveryOptions: { type: Array, default: () => [] },
    clientEmails: { type: Array, default: () => [] },
    permanentInstructions: { type: Object, default: () => ({}) },
});

const selectedDesigner = ref(props.order?.designer?.id ?? "");
const selectedSales = ref(props.order?.sales?.id ?? "");
const assigning = ref(false);
const assigningSales = ref(false);
const transitioning = ref(false);
const submitting = ref(false);
const submitFiles = ref([]);
const submitNotes = ref("");
const fileInput = ref(null);
const showResubmitForm = ref(false);

const showCreateRevisionModal = ref(false);
const revisionNotes = ref("");
const creatingRevision = ref(false);

// Submit Work delivery options (dynamic; first option = Option A, pre-filled from existing data)
const submitDeliveryOptions = ref([]);
const initSubmitOptions = () => {
    if (props.deliveryOptions && props.deliveryOptions.length) {
        submitDeliveryOptions.value = props.deliveryOptions.map(o => ({
            label: o.label,
            width: o.width ?? '',
            height: o.height ?? '',
            stitch_count: o.stitch_count ?? '',
        }));
    } else {
        submitDeliveryOptions.value = [{
            label: 'Option A',
            width: props.order?.submitted_width ?? '',
            height: props.order?.submitted_height ?? '',
            stitch_count: props.order?.submitted_stitch_count ?? '',
        }];
    }
};
initSubmitOptions();

const addSubmitOption = () => {
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const next = letters[submitDeliveryOptions.value.length] || String(submitDeliveryOptions.value.length + 1);
    submitDeliveryOptions.value.push({ label: `Option ${next}`, width: '', height: '', stitch_count: '' });
};

const removeSubmitOption = (index) => {
    if (submitDeliveryOptions.value.length > 1) {
        submitDeliveryOptions.value.splice(index, 1);
    }
};

const showDeliverModal = ref(false);
const deliverMessage = ref("");
const selectedFileIds = ref([]);
const delivering = ref(false);
const designerTip = ref("");

// Delivery options (editable in deliver modal)
const deliverOptionsForm = ref([]);
const initDeliverOptions = () => {
    if (props.deliveryOptions && props.deliveryOptions.length) {
        deliverOptionsForm.value = props.deliveryOptions.map(o => ({ ...o }));
    } else {
        deliverOptionsForm.value = [{
            id: null, label: 'Option A',
            width: props.order?.submitted_width ?? '',
            height: props.order?.submitted_height ?? '',
            stitch_count: props.order?.submitted_stitch_count ?? '',
            price: '', currency: 'USD',
        }];
    }
};

const addDeliveryOption = () => {
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const next = letters[deliverOptionsForm.value.length] || String(deliverOptionsForm.value.length + 1);
    deliverOptionsForm.value.push({ id: null, label: `Option ${next}`, width: '', height: '', stitch_count: '', price: '', currency: 'USD' });
};

const removeDeliveryOption = (index) => {
    if (deliverOptionsForm.value.length > 1) {
        deliverOptionsForm.value.splice(index, 1);
    }
};

// Email recipients
const selectedEmailRecipients = ref([]);
const initEmailRecipients = () => {
    // Pre-check the primary email by default
    const primary = props.clientEmails.find(e => e.is_primary);
    if (primary) {
        selectedEmailRecipients.value = [primary.email];
    } else if (props.clientEmails.length) {
        selectedEmailRecipients.value = [props.clientEmails[0].email];
    } else {
        selectedEmailRecipients.value = [];
    }
};

const toggleEmailRecipient = (email) => {
    const idx = selectedEmailRecipients.value.indexOf(email);
    if (idx === -1) {
        selectedEmailRecipients.value.push(email);
    } else {
        selectedEmailRecipients.value.splice(idx, 1);
    }
};

const openDeliverModal = () => {
    initDeliverOptions();
    initEmailRecipients();
    showDeliverModal.value = true;
};

const showCancelModal = ref(false);
const cancelReason = ref("");
const cancelling = ref(false);

const showStatusModal = ref(false);
const pendingStatus = ref(null);
const pendingTransition = ref(null);

const showCommissionTipModal = ref(false);
const selectedCommission = ref(null);
const commissionTipAmount = ref("");
const commissionTipNotes = ref("");
const updatingTip = ref(false);

const newComment = ref("");
const commentVisibility = ref("client");
const submittingComment = ref(false);

const page = usePage();
const highlightedCommentId = ref(null);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const commentId = params.get("comment");
    if (commentId) {
        highlightedCommentId.value = parseInt(commentId);
        nextTick(() => {
            const el = document.getElementById(`comment-${commentId}`);
            if (el) {
                el.scrollIntoView({ behavior: "smooth", block: "center" });
                // Remove highlight after animation
                setTimeout(() => {
                    highlightedCommentId.value = null;
                }, 3000);
            }
        });
    }
});

const formatSize = (size) => {
    if (!size) return "0 KB";
    const kb = size / 1024;
    if (kb < 1024) return `${kb.toFixed(1)} KB`;
    return `${(kb / 1024).toFixed(1)} MB`;
};

const assignDesigner = () => {
    if (!selectedDesigner.value) return;

    assigning.value = true;
    router.post(
        route("orders.assign", props.order.id),
        {
            designer_id: selectedDesigner.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                assigning.value = false;
            },
        }
    );
};

const unassignDesigner = () => {
    assigning.value = true;
    router.delete(route("orders.unassign", props.order.id), {
        preserveScroll: true,
        onFinish: () => {
            assigning.value = false;
            selectedDesigner.value = "";
        },
    });
};

const assignSales = () => {
    if (!selectedSales.value) return;

    assigningSales.value = true;
    router.post(
        route("orders.assign-sales", props.order.id),
        {
            sales_user_id: selectedSales.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                assigningSales.value = false;
            },
        }
    );
};

const unassignSales = () => {
    assigningSales.value = true;
    router.delete(route("orders.unassign-sales", props.order.id), {
        preserveScroll: true,
        onFinish: () => {
            assigningSales.value = false;
            selectedSales.value = "";
        },
    });
};

const submitCreateRevision = () => {
    creatingRevision.value = true;
    router.post(
        route("orders.create-revision", props.order.id),
        {
            notes: revisionNotes.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCreateRevisionModal.value = false;
                revisionNotes.value = "";
            },
            onFinish: () => {
                creatingRevision.value = false;
            },
        }
    );
};

const submitDeliver = () => {
    delivering.value = true;

    const data = {
        message: deliverMessage.value,
        file_ids: selectedFileIds.value,
        delivery_options: deliverOptionsForm.value.map(o => ({
            id: o.id || null,
            label: o.label,
            width: o.width || null,
            height: o.height || null,
            stitch_count: o.stitch_count ? parseInt(o.stitch_count) : null,
            price: o.price !== '' && o.price !== null ? parseFloat(o.price) : null,
            currency: o.currency || 'USD',
        })),
        email_recipients: selectedEmailRecipients.value,
    };

    // Add designer tip if enabled and has value
    if (props.enableDesignerTips && designerTip.value) {
        data.designer_tip = parseFloat(designerTip.value) || 0;
    }

    router.post(route("orders.deliver", props.order.id), data, {
        preserveScroll: true,
        onSuccess: () => {
            showDeliverModal.value = false;
            deliverMessage.value = "";
            selectedFileIds.value = [];
            designerTip.value = "";
        },
        onFinish: () => {
            delivering.value = false;
        },
    });
};

const toggleFileSelection = (fileId) => {
    const index = selectedFileIds.value.indexOf(fileId);
    if (index === -1) {
        selectedFileIds.value.push(fileId);
    } else {
        selectedFileIds.value.splice(index, 1);
    }
};

const selectAllFiles = () => {
    if (props.outputFiles?.length) {
        selectedFileIds.value = props.outputFiles.map((f) => f.id);
    }
};

const submitCancel = () => {
    cancelling.value = true;
    router.post(
        route("orders.cancel", props.order.id),
        {
            reason: cancelReason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCancelModal.value = false;
                cancelReason.value = "";
            },
            onFinish: () => {
                cancelling.value = false;
            },
        }
    );
};

const openCommissionTipModal = (commission) => {
    selectedCommission.value = commission;
    commissionTipAmount.value = commission.extra_amount.toString();
    commissionTipNotes.value = "";
    showCommissionTipModal.value = true;
};

const submitCommissionTip = () => {
    updatingTip.value = true;
    router.post(
        route("commissions.update-tip", selectedCommission.value.id),
        {
            extra_amount: parseFloat(commissionTipAmount.value) || 0,
            notes: commissionTipNotes.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showCommissionTipModal.value = false;
                selectedCommission.value = null;
                commissionTipAmount.value = "";
                commissionTipNotes.value = "";
            },
            onFinish: () => {
                updatingTip.value = false;
            },
        }
    );
};

const submitComment = () => {
    if (!newComment.value.trim()) return;

    submittingComment.value = true;
    router.post(
        route("orders.comments.store", props.order.id),
        {
            body: newComment.value,
            visibility: commentVisibility.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                newComment.value = "";
            },
            onFinish: () => {
                submittingComment.value = false;
            },
        }
    );
};

const showStatusConfirmation = (status, transition) => {
    pendingStatus.value = status;
    pendingTransition.value = transition;
    showStatusModal.value = true;
};

const confirmStatusChange = () => {
    if (!pendingStatus.value) return;

    transitioning.value = true;
    showStatusModal.value = false;

    router.patch(
        route("orders.status", props.order.id),
        {
            status: pendingStatus.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                transitioning.value = false;
                pendingStatus.value = null;
                pendingTransition.value = null;
            },
        }
    );
};

const handleFileSelect = (event) => {
    submitFiles.value = Array.from(event.target.files);
};

const removeFile = (index) => {
    submitFiles.value.splice(index, 1);
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const submitWork = () => {
    if (!submitFiles.value.length) return;

    submitting.value = true;

    const formData = new FormData();
    submitFiles.value.forEach((file) => {
        formData.append("files[]", file);
    });
    if (submitNotes.value) {
        formData.append("notes", submitNotes.value);
    }

    // Send all delivery options; first option drives submitted_width/height/stitch_count on the order
    submitDeliveryOptions.value.forEach((opt, i) => {
        formData.append(`delivery_options[${i}][label]`, opt.label || '');
        if (opt.width)        formData.append(`delivery_options[${i}][width]`, opt.width);
        if (opt.height)       formData.append(`delivery_options[${i}][height]`, opt.height);
        if (opt.stitch_count) formData.append(`delivery_options[${i}][stitch_count]`, opt.stitch_count);
    });

    router.post(route("orders.submit-work", props.order.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            submitFiles.value = [];
            submitNotes.value = "";
            initSubmitOptions();
            if (fileInput.value) {
                fileInput.value.value = "";
            }
            showResubmitForm.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
};

const showDeleteFileModal = ref(false);
const fileToDelete = ref(null);

const confirmDeleteFile = (file) => {
    fileToDelete.value = file;
    showDeleteFileModal.value = true;
};

const executeDeleteFile = () => {
    if (!fileToDelete.value) return;
    router.delete(route("orders.files.destroy", fileToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            showDeleteFileModal.value = false;
            fileToDelete.value = null;
        },
    });
};

const getButtonClass = (style) => {
    const classes = {
        primary: "bg-indigo-600 hover:bg-indigo-700 text-white",
        success: "bg-green-600 hover:bg-green-700 text-white",
        info: "bg-blue-600 hover:bg-blue-700 text-white",
        warning: "bg-yellow-500 hover:bg-yellow-600 text-white",
        danger: "bg-red-600 hover:bg-red-700 text-white",
        secondary: "bg-slate-600 hover:bg-slate-700 text-white",
    };
    return classes[style] || classes.secondary;
};

const statusBadgeClass = (status) => {
    const map = {
        received: "bg-slate-100 text-slate-700",
        assigned: "bg-blue-100 text-blue-700",
        in_progress: "bg-indigo-100 text-indigo-700",
        submitted: "bg-purple-100 text-purple-700",
        in_review: "bg-cyan-100 text-cyan-700",
        approved: "bg-green-100 text-green-700",
        delivered: "bg-emerald-100 text-emerald-700",
        closed: "bg-slate-100 text-slate-600",
        cancelled: "bg-red-100 text-red-700",
    };
    return map[status] || "bg-slate-100 text-slate-700";
};

const priorityBadgeClass = (priority) => {
    return priority === "rush"
        ? "bg-red-50 text-red-700 ring-1 ring-red-200"
        : "bg-slate-50 text-slate-600 ring-1 ring-slate-200";
};

// Computed for submit file errors (covers both 'files' and 'files.0', 'files.1', etc.)
const submitFileErrors = computed(() => {
    const errors = page.props.errors ?? {};
    const msgs = Object.entries(errors)
        .filter(([key]) => key === 'files' || key.startsWith('files.'))
        .map(([, val]) => val);
    return [...new Set(msgs)].join(' ');
});

const hasPermanentInstructions = computed(() => {
    const pi = props.permanentInstructions;
    if (!pi) return false;
    return (
        pi.special_offer_note || pi.price_instructions ||
        pi.for_digitizer || pi.appreciation_bonus ||
        (pi.custom && pi.custom.length > 0)
    );
});

// Accept attribute for file input derived from allowedOutputExtensions
const fileInputAccept = computed(() => {
    if (!props.allowedOutputExtensions) return '';
    return props.allowedOutputExtensions
        .split(',')
        .map(ext => ext.trim())
        .filter(Boolean)
        .map(ext => ext.startsWith('.') ? ext : `.${ext}`)
        .join(',');
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-semibold text-slate-900">
                                {{ order.order_number }}
                            </h2>
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize',
                                    statusBadgeClass(order.status),
                                ]"
                            >
                                {{ (order.status || "").split("_").join(" ") }}
                            </span>
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize',
                                    priorityBadgeClass(order.priority),
                                ]"
                            >
                                {{ order.priority }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ order.title }}
                        </p>
                        <p
                            v-if="order.parent_order"
                            class="mt-0.5 text-xs text-indigo-600"
                        >
                            Revision of:
                            <Link
                                :href="route('orders.show', order.parent_order.id)"
                                class="font-medium hover:text-indigo-900"
                            >
                                {{ order.parent_order.order_number }}
                            </Link>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('orders.edit', order.id)"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('orders.index')"
                        class="inline-flex items-center rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800"
                    >
                        Back
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash success banner -->
                <div
                    v-if="page.props.flash?.success"
                    class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 flex items-center gap-3"
                >
                    <svg class="h-5 w-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium text-green-800">{{ page.props.flash.success }}</p>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main Content (Left) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Order Details -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Order Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3"
                                >
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Type
                                        </dt>
                                        <dd
                                            class="mt-1 text-sm text-slate-900 capitalize"
                                        >
                                            {{
                                                (order.type || "")
                                                    .split("_")
                                                    .join(" ")
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Client
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.client.name }}
                                        </dd>
                                        <dd class="text-xs text-slate-500">
                                            {{ order.client.email }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Designer
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{
                                                order.designer?.name ??
                                                "Unassigned"
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Sales
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{
                                                order.sales?.name ??
                                                "Unassigned"
                                            }}
                                        </dd>
                                    </div>
                                    <div v-if="order.po_number">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            PO #
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.po_number }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Price
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{
                                                order.price_amount
                                                    ? `${order.currency} ${order.price_amount}`
                                                    : "—"
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Due Date
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{
                                                order.due_at
                                                    ? formatDate(order.due_at)
                                                    : "—"
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Created
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{
                                                formatDate(
                                                    order.created_at,
                                                    true
                                                )
                                            }}
                                        </dd>
                                    </div>
                                </dl>

                                <!-- Submitted Work Details -->
                                <div
                                    v-if="order.submitted_width || order.submitted_height || order.submitted_stitch_count"
                                    class="mt-4 pt-4 border-t border-slate-100"
                                >
                                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wide mb-3">
                                        Submitted Work Details
                                    </h4>
                                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                                        <div v-if="order.submitted_width">
                                            <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                                                Submitted Width
                                            </dt>
                                            <dd class="mt-1 text-sm text-slate-900">
                                                {{ order.submitted_width }}
                                            </dd>
                                        </div>
                                        <div v-if="order.submitted_height">
                                            <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                                                Submitted Height
                                            </dt>
                                            <dd class="mt-1 text-sm text-slate-900">
                                                {{ order.submitted_height }}
                                            </dd>
                                        </div>
                                        <div v-if="order.submitted_stitch_count">
                                            <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">
                                                Stitch Count
                                            </dt>
                                            <dd class="mt-1 text-sm text-slate-900">
                                                {{ order.submitted_stitch_count?.toLocaleString() }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Digitizing Details -->
                        <div
                            v-if="
                                order.type === 'digitizing' &&
                                (order.height ||
                                    order.width ||
                                    order.placement ||
                                    order.file_format)
                            "
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Digitizing Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4"
                                >
                                    <div v-if="order.height">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Height
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.height }}"
                                        </dd>
                                    </div>
                                    <div v-if="order.width">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Width
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.width }}"
                                        </dd>
                                    </div>
                                    <div v-if="order.placement">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Placement
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.placement }}
                                        </dd>
                                    </div>
                                    <div v-if="order.file_format">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            File Format
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.file_format }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Patch Details -->
                        <div
                            v-if="
                                order.type === 'patch' &&
                                (order.patch_type ||
                                    order.quantity ||
                                    order.height ||
                                    order.backing)
                            "
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Patch Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3"
                                >
                                    <div v-if="order.patch_type">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Patch Type
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.patch_type }}
                                        </dd>
                                    </div>
                                    <div v-if="order.quantity">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Quantity
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.quantity }}
                                        </dd>
                                    </div>
                                    <div v-if="order.height">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Size
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.height }}" x
                                            {{ order.width }}"
                                        </dd>
                                    </div>
                                    <div v-if="order.backing">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Backing
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.backing }}
                                        </dd>
                                    </div>
                                    <div v-if="order.placement">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Placement
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.placement }}
                                        </dd>
                                    </div>
                                    <div v-if="order.merrow_border">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Merrow Border
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.merrow_border }}
                                        </dd>
                                    </div>
                                    <div v-if="order.num_colors">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Colors
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.num_colors }}
                                        </dd>
                                    </div>
                                    <div v-if="order.fabric">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Fabric
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.fabric }}
                                        </dd>
                                    </div>
                                    <div v-if="order.need_by">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Need By
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ formatDate(order.need_by) }}
                                        </dd>
                                    </div>
                                </dl>
                                <div
                                    v-if="order.shipping_address"
                                    class="mt-4 pt-4 border-t border-slate-100"
                                >
                                    <dt
                                        class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                    >
                                        Shipping Address
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm text-slate-900 whitespace-pre-line"
                                    >
                                        {{ order.shipping_address }}
                                    </dd>
                                </div>
                            </div>
                        </div>

                        <!-- Vector Details -->
                        <div
                            v-if="
                                order.type === 'vector' &&
                                (order.color_type ||
                                    order.vector_order_type ||
                                    order.required_format)
                            "
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Vector Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4"
                                >
                                    <div v-if="order.color_type">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Color Type
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.color_type }}
                                        </dd>
                                    </div>
                                    <div v-if="order.num_colors">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Colors
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.num_colors }}
                                        </dd>
                                    </div>
                                    <div v-if="order.vector_order_type">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Order Type
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.vector_order_type }}
                                        </dd>
                                    </div>
                                    <div v-if="order.required_format">
                                        <dt
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Format
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900">
                                            {{ order.required_format }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Instructions
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <p
                                    class="whitespace-pre-line text-sm text-slate-700 leading-relaxed"
                                >
                                    {{
                                        order.instructions ||
                                        "No instructions provided."
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Revision Orders -->
                        <div
                            v-if="revisionOrders?.length"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Revision Orders ({{ revisionOrders.length }})
                                </h3>
                            </div>
                            <div class="divide-y divide-slate-50">
                                <div
                                    v-for="rev in revisionOrders"
                                    :key="rev.id"
                                    class="px-5 py-3 flex items-center justify-between"
                                >
                                    <div>
                                        <Link
                                            :href="route('orders.show', rev.id)"
                                            class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{ rev.order_number }}
                                        </Link>
                                        <p class="text-xs text-slate-500">
                                            {{ formatDate(rev.created_at, true) }}
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize',
                                            statusBadgeClass(rev.status),
                                        ]"
                                    >
                                        {{ (rev.status || '').split('_').join(' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Input Files -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Input Files
                                </h3>
                                <a
                                    v-if="downloadInputZipUrl && inputFiles?.length"
                                    :href="downloadInputZipUrl"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200 hover:bg-indigo-100 transition"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download All
                                </a>
                            </div>
                            <div v-if="inputFiles?.length">
                                <div
                                    v-for="file in inputFiles"
                                    :key="file.id"
                                    class="flex items-center justify-between px-5 py-3 border-b border-slate-50 last:border-0"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-medium text-slate-900 truncate"
                                        >
                                            {{ file.original_name }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ formatSize(file.size) }} &bull;
                                            {{
                                                formatDate(
                                                    file.uploaded_at,
                                                    true
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="ml-3 flex items-center gap-2">
                                        <a
                                            :href="file.download_url"
                                            class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100"
                                        >
                                            Download
                                        </a>
                                        <button
                                            v-if="file.can_delete"
                                            type="button"
                                            class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-600 ring-1 ring-red-200 hover:bg-red-100"
                                            @click="confirmDeleteFile(file)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="px-5 py-4">
                                <p class="text-sm text-slate-500">
                                    No input files uploaded.
                                </p>
                            </div>
                        </div>

                        <!-- Output Files -->
                        <div
                            v-if="outputFiles?.length"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Output Files
                                </h3>
                                <a
                                    v-if="downloadOutputZipUrl"
                                    :href="downloadOutputZipUrl"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200 hover:bg-indigo-100 transition"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download All
                                </a>
                            </div>
                            <div>
                                <div
                                    v-for="file in outputFiles"
                                    :key="file.id"
                                    class="flex items-center justify-between px-5 py-3 border-b border-slate-50 last:border-0"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p
                                                class="text-sm font-medium text-slate-900 truncate"
                                            >
                                                {{ file.original_name }}
                                            </p>
                                            <span
                                                v-if="file.is_delivered"
                                                class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20"
                                            >
                                                Delivered
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500">
                                            {{ formatSize(file.size) }} &bull;
                                            {{
                                                formatDate(
                                                    file.uploaded_at,
                                                    true
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="ml-3 flex items-center gap-2">
                                        <a
                                            :href="file.download_url"
                                            class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100"
                                        >
                                            Download
                                        </a>
                                        <button
                                            v-if="file.can_delete"
                                            type="button"
                                            class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-600 ring-1 ring-red-200 hover:bg-red-100"
                                            @click="confirmDeleteFile(file)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Work Form -->
                        <div
                            v-if="canSubmitWork"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <!-- Collapsed state: work already submitted, user hasn't clicked Resubmit yet -->
                            <template v-if="alreadySubmitted && !showResubmitForm">
                                <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                            Work Already Submitted
                                        </span>
                                        <span class="text-xs text-slate-500">Output files have been uploaded for this order.</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 shrink-0"
                                        @click="showResubmitForm = true"
                                    >
                                        Resubmit Work
                                    </button>
                                </div>
                            </template>

                            <!-- Full form (initial submit OR resubmit mode) -->
                            <template v-else>
                            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    {{ alreadySubmitted ? 'Resubmit Work' : 'Submit Work' }}
                                </h3>
                                <button
                                    v-if="alreadySubmitted"
                                    type="button"
                                    class="text-xs text-slate-400 hover:text-slate-600"
                                    @click="showResubmitForm = false"
                                >
                                    Cancel
                                </button>
                            </div>

                            <!-- Resubmit warning banner -->
                            <div v-if="alreadySubmitted" class="mx-5 mt-4 rounded-md bg-amber-50 border border-amber-200 p-3">
                                <div class="flex items-start gap-2">
                                    <svg class="h-4 w-4 mt-0.5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <p class="text-xs font-semibold text-amber-800">Work has already been submitted</p>
                                        <p class="text-xs text-amber-700 mt-0.5">Uploading new files will add to the existing output files. The order status will not change.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="px-5 py-4 space-y-4">
                                <p class="text-xs text-slate-500">
                                    Upload your completed files to submit for
                                    review.
                                    <span v-if="allowedOutputExtensions">
                                        Allowed:
                                        {{ allowedOutputExtensions }}.</span
                                    >
                                    Max size: {{ maxUploadMb }}MB per file.
                                </p>

                                <div>
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        multiple
                                        :accept="fileInputAccept"
                                        class="block w-full text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                        @change="handleFileSelect"
                                    />
                                    <p
                                        v-if="submitFileErrors"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ submitFileErrors }}
                                    </p>
                                </div>

                                <div
                                    v-if="submitFiles.length"
                                    class="rounded-md border border-slate-200 divide-y divide-slate-100"
                                >
                                    <div
                                        v-for="(file, index) in submitFiles"
                                        :key="index"
                                        class="flex items-center justify-between px-3 py-2"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-medium text-slate-900"
                                            >
                                                {{ file.name }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ formatSize(file.size) }}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            class="text-xs text-red-500 hover:text-red-700"
                                            @click="removeFile(index)"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>

                                <!-- Dynamic delivery options -->
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="text-xs font-semibold text-slate-700">Delivery Options</p>
                                        <button
                                            type="button"
                                            @click="addSubmitOption"
                                            class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            + Add Option
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(opt, idx) in submitDeliveryOptions"
                                            :key="idx"
                                            class="rounded-md border border-slate-200 bg-white p-2.5"
                                        >
                                            <div class="flex items-center justify-between mb-2">
                                                <input
                                                    v-model="opt.label"
                                                    type="text"
                                                    placeholder="Option A"
                                                    class="block w-28 rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                />
                                                <button
                                                    v-if="submitDeliveryOptions.length > 1"
                                                    type="button"
                                                    @click="removeSubmitOption(idx)"
                                                    class="text-red-500 hover:text-red-700 text-xs"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                            <div class="grid grid-cols-3 gap-2">
                                                <div>
                                                    <label class="block text-xs text-slate-500">Width</label>
                                                    <input
                                                        v-model="opt.width"
                                                        type="text"
                                                        placeholder='3.5"'
                                                        class="mt-0.5 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-500">Height</label>
                                                    <input
                                                        v-model="opt.height"
                                                        type="text"
                                                        placeholder='2.5"'
                                                        class="mt-0.5 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-500">Stitches</label>
                                                    <input
                                                        v-model="opt.stitch_count"
                                                        type="number"
                                                        min="0"
                                                        placeholder="12000"
                                                        class="mt-0.5 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="submit_notes"
                                        class="block text-xs font-medium text-slate-700"
                                        >Notes (optional)</label
                                    >
                                    <textarea
                                        v-model="submitNotes"
                                        id="submit_notes"
                                        rows="2"
                                        placeholder="Any notes about your submission..."
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    ></textarea>
                                </div>

                                <button
                                    type="button"
                                    :disabled="
                                        !submitFiles.length || submitting
                                    "
                                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-700 disabled:opacity-50"
                                    @click="submitWork"
                                >
                                    {{
                                        submitting
                                            ? "Submitting..."
                                            : alreadySubmitted ? "Resubmit Work" : "Submit Work"
                                    }}
                                </button>
                            </div>
                            </template>
                        </div>
                    </div>

                    <!-- Sidebar (Right) -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <div
                            v-if="
                                allowedTransitions?.length ||
                                canCreateRevision ||
                                canDeliver ||
                                canCancel
                            "
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Actions
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="transition in allowedTransitions"
                                        :key="transition.value"
                                        type="button"
                                        :disabled="transitioning"
                                        :class="[
                                            'inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium shadow-sm disabled:opacity-50',
                                            getButtonClass(transition.style),
                                        ]"
                                        @click="
                                            showStatusConfirmation(
                                                transition.value,
                                                transition
                                            )
                                        "
                                    >
                                        {{ transition.label }}
                                    </button>
                                    <button
                                        v-if="canDeliver"
                                        type="button"
                                        class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium shadow-sm bg-green-600 hover:bg-green-700 text-white"
                                        @click="openDeliverModal(); selectAllFiles();"
                                    >
                                        Deliver Order
                                    </button>
                                    <button
                                        v-if="canCreateRevision"
                                        type="button"
                                        class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium shadow-sm bg-indigo-600 hover:bg-indigo-700 text-white"
                                        @click="showCreateRevisionModal = true"
                                    >
                                        Create Revision
                                    </button>
                                    <button
                                        v-if="canCancel"
                                        type="button"
                                        class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium shadow-sm bg-red-600 hover:bg-red-700 text-white"
                                        @click="showCancelModal = true"
                                    >
                                        Cancel Order
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Designer Assignment -->
                        <div
                            v-if="canAssign"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Assign Designer
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <div class="space-y-3">
                                    <select
                                        v-model="selectedDesigner"
                                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :disabled="assigning"
                                    >
                                        <option value="">Unassigned</option>
                                        <option
                                            v-for="designer in designers"
                                            :key="designer.id"
                                            :value="designer.id"
                                        >
                                            {{ designer.name }}
                                        </option>
                                    </select>
                                    <div class="flex gap-2">
                                        <button
                                            v-if="
                                                selectedDesigner &&
                                                selectedDesigner !==
                                                    order.designer?.id
                                            "
                                            type="button"
                                            class="flex-1 inline-flex justify-center items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                            :disabled="assigning"
                                            @click="assignDesigner"
                                        >
                                            Assign
                                        </button>
                                        <button
                                            v-if="
                                                order.designer &&
                                                selectedDesigner === ''
                                            "
                                            type="button"
                                            class="flex-1 inline-flex justify-center items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                            :disabled="assigning"
                                            @click="unassignDesigner"
                                        >
                                            Unassign
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Assignment -->
                        <div
                            v-if="canAssign && salesUsers?.length"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Assign Sales
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <div class="space-y-3">
                                    <select
                                        v-model="selectedSales"
                                        class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :disabled="assigningSales"
                                    >
                                        <option value="">Unassigned</option>
                                        <option
                                            v-for="user in salesUsers"
                                            :key="user.id"
                                            :value="user.id"
                                        >
                                            {{ user.name }}
                                        </option>
                                    </select>
                                    <div class="flex gap-2">
                                        <button
                                            v-if="
                                                selectedSales &&
                                                selectedSales !==
                                                    order.sales?.id
                                            "
                                            type="button"
                                            class="flex-1 inline-flex justify-center items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                            :disabled="assigningSales"
                                            @click="assignSales"
                                        >
                                            Assign
                                        </button>
                                        <button
                                            v-if="
                                                order.sales &&
                                                selectedSales === ''
                                            "
                                            type="button"
                                            class="flex-1 inline-flex justify-center items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                            :disabled="assigningSales"
                                            @click="unassignSales"
                                        >
                                            Unassign
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Commissions -->
                        <div
                            v-if="commissions?.length"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Commissions & Earnings
                                </h3>
                            </div>
                            <div class="divide-y divide-slate-50">
                                <div
                                    v-for="commission in commissions"
                                    :key="commission.id"
                                    class="px-5 py-4"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-center gap-2 mb-1"
                                            >
                                                <span
                                                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                                    :class="{
                                                        'bg-blue-50 text-blue-700 ring-blue-600/20':
                                                            commission.role_type ===
                                                            'sales',
                                                        'bg-purple-50 text-purple-700 ring-purple-600/20':
                                                            commission.role_type ===
                                                            'designer',
                                                    }"
                                                >
                                                    {{ commission.role_label }}
                                                </span>
                                                <span
                                                    class="text-sm font-medium text-slate-900"
                                                >
                                                    {{
                                                        commission.user?.name ??
                                                        "Unknown"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                Earned on
                                                {{
                                                    commission.earned_on_status
                                                }}
                                                •
                                                {{
                                                    formatDate(
                                                        commission.earned_at
                                                    )
                                                }}
                                            </div>
                                            <div
                                                v-if="commission.notes"
                                                class="mt-2 text-xs text-slate-600 bg-slate-50 rounded px-2 py-1"
                                            >
                                                {{ commission.notes }}
                                            </div>
                                        </div>
                                        <div
                                            class="flex flex-col items-end gap-1 ml-4"
                                        >
                                            <div class="text-right">
                                                <div
                                                    class="text-sm font-semibold text-slate-900"
                                                >
                                                    {{ commission.currency }}
                                                    {{
                                                        parseFloat(
                                                            commission.total_amount || 0
                                                        ).toFixed(2)
                                                    }}
                                                </div>
                                                <div
                                                    v-if="
                                                        parseFloat(commission.extra_amount || 0) >
                                                        0
                                                    "
                                                    class="text-xs text-slate-500"
                                                >
                                                    Base:
                                                    {{ commission.currency }}
                                                    {{
                                                        parseFloat(
                                                            commission.base_amount || 0
                                                        ).toFixed(2)
                                                    }}
                                                </div>
                                                <div
                                                    v-if="
                                                        parseFloat(commission.extra_amount || 0) >
                                                        0
                                                    "
                                                    class="text-xs text-indigo-600 font-medium"
                                                >
                                                    + Tip:
                                                    {{ commission.currency }}
                                                    {{
                                                        parseFloat(
                                                            commission.extra_amount || 0
                                                        ).toFixed(2)
                                                    }}
                                                </div>
                                            </div>
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                :class="{
                                                    'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20':
                                                        commission.is_paid,
                                                    'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20':
                                                        !commission.is_paid,
                                                }"
                                            >
                                                {{
                                                    commission.is_paid
                                                        ? "Paid"
                                                        : "Unpaid"
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        v-if="
                                            canAssign &&
                                            commission.role_type === 'designer'
                                        "
                                        class="mt-3 flex justify-end"
                                    >
                                        <button
                                            @click="
                                                openCommissionTipModal(
                                                    commission
                                                )
                                            "
                                            type="button"
                                            class="text-xs text-indigo-600 hover:text-indigo-900 font-medium"
                                        >
                                            {{
                                                commission.extra_amount > 0
                                                    ? "Edit Tip"
                                                    : "Add Tip"
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Info -->
                        <div
                            v-if="invoiceInfo?.is_invoiced || invoiceInfo?.can_create_invoice"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Invoice
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <div v-if="invoiceInfo.linked_invoice" class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-slate-900">
                                            <Link
                                                :href="route('invoices.show', invoiceInfo.linked_invoice.id)"
                                                class="font-medium text-indigo-600 hover:text-indigo-900"
                                            >
                                                {{ invoiceInfo.linked_invoice.number }}
                                            </Link>
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Status: {{ invoiceInfo.linked_invoice.status_label }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                        Invoiced
                                    </span>
                                </div>
                                <div v-else-if="invoiceInfo.can_create_invoice">
                                    <p class="text-sm text-slate-500 mb-3">This order has not been invoiced yet.</p>
                                    <Link
                                        :href="route('invoices.create', { orders: [order.id], client_id: order.client.id })"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                                    >
                                        Create Invoice
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Permanent Instructions -->
                        <div
                            v-if="hasPermanentInstructions"
                            class="rounded-lg border border-amber-200 bg-amber-50 shadow-sm"
                        >
                            <div class="px-5 py-4 border-b border-amber-200">
                                <h3 class="text-sm font-semibold text-amber-900">Client Instructions</h3>
                            </div>
                            <div class="px-5 py-4 space-y-3">
                                <div v-if="permanentInstructions.special_offer_note">
                                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Special Offer / Note</p>
                                    <p class="mt-0.5 text-sm text-slate-800 whitespace-pre-line">{{ permanentInstructions.special_offer_note }}</p>
                                </div>
                                <div v-if="permanentInstructions.price_instructions">
                                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Price Instructions</p>
                                    <p class="mt-0.5 text-sm text-slate-800 whitespace-pre-line">{{ permanentInstructions.price_instructions }}</p>
                                </div>
                                <div v-if="permanentInstructions.for_digitizer">
                                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">For Digitizer</p>
                                    <p class="mt-0.5 text-sm text-slate-800 whitespace-pre-line">{{ permanentInstructions.for_digitizer }}</p>
                                </div>
                                <div v-if="permanentInstructions.appreciation_bonus">
                                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">Appreciation Bonus</p>
                                    <p class="mt-0.5 text-sm text-slate-800">${{ permanentInstructions.appreciation_bonus }}</p>
                                </div>
                                <template v-if="permanentInstructions.custom && permanentInstructions.custom.length">
                                    <div v-for="(item, idx) in permanentInstructions.custom" :key="idx">
                                        <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide">{{ item.key }}</p>
                                        <p class="mt-0.5 text-sm text-slate-800 whitespace-pre-line">{{ item.value }}</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Activity Timeline -->
                        <div
                            v-if="timeline?.length"
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Activity
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <OrderTimeline :events="timeline" />
                            </div>
                        </div>

                        <!-- Comments -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-slate-200"
                        >
                            <div class="px-5 py-4 border-b border-slate-100">
                                <h3 class="text-sm font-semibold text-slate-900">
                                    Comments
                                </h3>
                            </div>
                            <div class="px-5 py-4 space-y-4">
                                <!-- Comment List -->
                                <div
                                    v-if="comments?.length > 0"
                                    class="space-y-3 mb-4"
                                >
                                    <div
                                        v-for="comment in comments"
                                        :key="comment.id"
                                        :id="`comment-${comment.id}`"
                                        class="border-l-2 pl-4 rounded-r-md transition-all duration-700"
                                        :class="[
                                            comment.visibility === 'internal'
                                                ? 'border-yellow-300 bg-yellow-50/30'
                                                : 'border-indigo-200',
                                            highlightedCommentId === comment.id
                                                ? 'bg-indigo-50 ring-2 ring-indigo-300 !border-indigo-400'
                                                : '',
                                        ]"
                                    >
                                        <div
                                            class="flex items-start justify-between"
                                        >
                                            <div>
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <p
                                                        class="text-sm font-medium text-slate-900"
                                                    >
                                                        {{ comment.user.name }}
                                                    </p>
                                                    <span
                                                        v-if="
                                                            comment.visibility ===
                                                            'internal'
                                                        "
                                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800"
                                                    >
                                                        Internal
                                                    </span>
                                                </div>
                                                <p
                                                    class="text-xs text-slate-500"
                                                >
                                                    {{
                                                        formatDate(
                                                            comment.created_at
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                        <p
                                            class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"
                                        >
                                            {{ comment.body }}
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-slate-500">
                                    No comments yet.
                                </p>

                                <!-- Add Comment Form -->
                                <div class="pt-4 border-t border-slate-200">
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-2"
                                        >Add a comment</label
                                    >
                                    <textarea
                                        v-model="newComment"
                                        rows="3"
                                        class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        placeholder="Type your comment..."
                                    ></textarea>
                                    <div
                                        class="mt-2 flex items-center justify-between"
                                    >
                                        <div
                                            v-if="canAssign"
                                            class="flex items-center gap-4"
                                        >
                                            <label class="flex items-center">
                                                <input
                                                    v-model="commentVisibility"
                                                    type="radio"
                                                    value="client"
                                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-slate-700"
                                                    >Visible to client</span
                                                >
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="commentVisibility"
                                                    type="radio"
                                                    value="internal"
                                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-slate-700"
                                                    >Internal only</span
                                                >
                                            </label>
                                        </div>
                                        <button
                                            @click="submitComment"
                                            :disabled="
                                                !newComment.trim() ||
                                                submittingComment
                                            "
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        >
                                            {{
                                                submittingComment
                                                    ? "Posting..."
                                                    : "Post Comment"
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Deliver Order Modal -->
        <div v-if="showDeliverModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="fixed inset-0 bg-slate-500/75"
                    @click="showDeliverModal = false"
                ></div>
                <div
                    class="relative w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl max-h-[90vh] overflow-y-auto"
                >
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ alreadyDelivered ? 'Re-send Delivery' : 'Deliver Order' }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Send the completed work to the client via email.
                    </p>

                    <!-- Re-delivery warning banner -->
                    <div v-if="alreadyDelivered" class="mt-3 rounded-md bg-amber-50 border border-amber-200 p-3">
                        <div class="flex items-start gap-2">
                            <svg class="h-4 w-4 mt-0.5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-xs font-semibold text-amber-800">This order has already been delivered</p>
                                <p class="text-xs text-amber-700 mt-0.5">You are sending the files again. A new delivery email will be sent to the client without changing the order status.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-5">
                        <!-- Delivery Options -->
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-semibold text-slate-800">Delivery Options</label>
                                <button
                                    type="button"
                                    @click="addDeliveryOption"
                                    class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    + Add Option
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="(opt, idx) in deliverOptionsForm"
                                    :key="idx"
                                    class="rounded-md border border-slate-200 bg-white p-3"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <input
                                            v-model="opt.label"
                                            type="text"
                                            placeholder="Option A"
                                            class="block w-32 rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <button
                                            v-if="deliverOptionsForm.length > 1"
                                            type="button"
                                            @click="removeDeliveryOption(idx)"
                                            class="text-red-500 hover:text-red-700 text-xs font-medium"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        <div>
                                            <label class="text-xs text-slate-500">Width</label>
                                            <input v-model="opt.width" type="text" placeholder="e.g. 4.5 in"
                                                class="mt-0.5 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-slate-500">Height</label>
                                            <input v-model="opt.height" type="text" placeholder="e.g. 3.2 in"
                                                class="mt-0.5 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-slate-500">Stitches</label>
                                            <input v-model="opt.stitch_count" type="number" min="0" placeholder="5000"
                                                class="mt-0.5 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="text-xs text-slate-500">Price</label>
                                            <input v-model="opt.price" type="number" step="0.01" min="0" placeholder="0.00"
                                                class="mt-0.5 block w-full rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Email Recipients -->
                        <div v-if="clientEmails && clientEmails.length" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <label class="block text-sm font-semibold text-slate-800 mb-2">Email Recipients</label>
                            <div class="space-y-1.5">
                                <label
                                    v-for="entry in clientEmails"
                                    :key="entry.email"
                                    class="flex items-center gap-3 cursor-pointer rounded-md px-2 py-1.5 hover:bg-slate-100"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="selectedEmailRecipients.includes(entry.email)"
                                        @change="toggleEmailRecipient(entry.email)"
                                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-slate-800">{{ entry.email }}</span>
                                    <span v-if="entry.label" class="rounded-full bg-slate-200 px-1.5 py-0.5 text-xs text-slate-600">{{ entry.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Message -->
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                >Message to Client (optional)</label
                            >
                            <textarea
                                v-model="deliverMessage"
                                rows="3"
                                placeholder="Add a message or feedback for the client..."
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            ></textarea>
                        </div>

                        <!-- Designer Tip (if enabled) -->
                        <div
                            v-if="enableDesignerTips && order?.designer"
                            class="rounded-lg border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <label
                                class="block text-sm font-medium text-indigo-900"
                                >Designer Tip (optional)</label
                            >
                            <p class="text-xs text-indigo-700 mt-0.5 mb-2">
                                Reward exceptional work! This tip will be added
                                to {{ order.designer.name }}'s earnings.
                            </p>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-sm font-medium text-indigo-700"
                                    >{{ currency || "USD" }}</span
                                >
                                <input
                                    v-model="designerTip"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="block w-32 rounded-md border-indigo-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>
                        </div>

                        <!-- Files to Attach -->
                        <div v-if="outputFiles?.length">
                            <label
                                class="block text-sm font-medium text-slate-700"
                                >Attach Files</label
                            >
                            <p class="text-xs text-slate-500 mt-0.5">
                                Select which files to send as email attachments.
                            </p>
                            <div
                                class="mt-2 max-h-48 overflow-y-auto rounded-md border border-slate-200 divide-y divide-slate-100"
                            >
                                <label
                                    v-for="file in outputFiles"
                                    :key="file.id"
                                    class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="selectedFileIds.includes(file.id)"
                                        @change="toggleFileSelection(file.id)"
                                        class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-medium text-slate-900 truncate"
                                        >
                                            {{ file.original_name }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ formatSize(file.size) }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ selectedFileIds.length }} of
                                {{ outputFiles.length }} files selected
                            </p>
                        </div>

                        <div
                            v-else
                            class="rounded-md border border-yellow-200 bg-yellow-50 p-3"
                        >
                            <p class="text-sm text-yellow-700">
                                No output files available. The email will be
                                sent without attachments.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            @click="showDeliverModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="delivering"
                            class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                            @click="submitDeliver"
                        >
                            {{
                                delivering
                                    ? "Delivering..."
                                    : "Deliver & Notify Client"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Revision Order Modal -->
        <div
            v-if="showCreateRevisionModal"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="fixed inset-0 bg-slate-500/75"
                    @click="showCreateRevisionModal = false"
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-slate-900">
                        Create Revision Order
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        A new revision order will be created with all details copied from this order.
                    </p>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notes / Instructions (optional)</label>
                        <textarea
                            v-model="revisionNotes"
                            rows="4"
                            placeholder="Describe what changes are needed for the revision..."
                            class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        ></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            @click="showCreateRevisionModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="creatingRevision"
                            class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            @click="submitCreateRevision"
                        >
                            {{
                                creatingRevision
                                    ? "Creating..."
                                    : "Create Revision Order"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Order Modal -->
        <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="fixed inset-0 bg-slate-500/75"
                    @click="showCancelModal = false"
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-slate-900">
                        Cancel Order
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        This action cannot be undone. Please provide a reason.
                    </p>
                    <div class="mt-4">
                        <textarea
                            v-model="cancelReason"
                            rows="3"
                            placeholder="Reason for cancellation..."
                            class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        ></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            @click="showCancelModal = false"
                        >
                            Keep Order
                        </button>
                        <button
                            type="button"
                            :disabled="!cancelReason.trim() || cancelling"
                            class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                            @click="submitCancel"
                        >
                            {{ cancelling ? "Cancelling..." : "Cancel Order" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Change Confirmation Modal -->
        <div v-if="showStatusModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="fixed inset-0 bg-slate-500/75"
                    @click="
                        showStatusModal = false;
                        pendingStatus = null;
                        pendingTransition = null;
                    "
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-slate-900">
                        Confirm Status Change
                    </h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Are you sure you want to change the order status to
                        <span class="font-semibold">{{
                            pendingTransition?.label
                        }}</span
                        >?
                    </p>
                    <div class="mt-5 flex justify-end gap-3">
                        <Button
                            as="button"
                            @click="
                                showStatusModal = false;
                                pendingStatus = null;
                                pendingTransition = null;
                            "
                        >
                            Cancel
                        </Button>
                        <button
                            type="button"
                            :disabled="transitioning"
                            :class="[
                                'rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-50',
                                getButtonClass(pendingTransition?.style),
                            ]"
                            @click="confirmStatusChange"
                        >
                            {{ transitioning ? "Processing..." : "Confirm" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commission Tip Modal -->
        <div
            v-if="showCommissionTipModal"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="fixed inset-0 bg-slate-500/75"
                    @click="showCommissionTipModal = false"
                ></div>
                <div
                    class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{
                            selectedCommission?.extra_amount > 0
                                ? "Edit"
                                : "Add"
                        }}
                        Commission Tip
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Add an extra amount (tip/bonus) for
                        {{ selectedCommission?.user?.name }}.
                    </p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                >Tip Amount *</label
                            >
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <span class="text-slate-500 sm:text-sm">{{
                                        currency
                                    }}</span>
                                </div>
                                <input
                                    v-model="commissionTipAmount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="block w-full pl-12 pr-3 py-2 border-slate-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="0.00"
                                />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                Current base: {{ currency }}
                                {{
                                    parseFloat(
                                        selectedCommission?.base_amount || 0
                                    ).toFixed(2)
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700"
                                >Notes (Optional)</label
                            >
                            <textarea
                                v-model="commissionTipNotes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Add a note about this tip..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <Button
                            as="button"
                            @click="showCommissionTipModal = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            as="button"
                            variant="primary"
                            :disabled="!commissionTipAmount || updatingTip"
                            @click="submitCommissionTip"
                        >
                            {{ updatingTip ? "Updating..." : "Update Tip" }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    <!-- Delete File Confirmation -->
    <ConfirmModal
        :show="showDeleteFileModal"
        title="Delete file"
        :message="fileToDelete ? `Are you sure you want to delete &quot;${fileToDelete.original_name}&quot;? This cannot be undone.` : ''"
        confirm-label="Delete"
        @close="showDeleteFileModal = false; fileToDelete = null;"
        @confirm="executeDeleteFile"
    />
    </AppLayout>
</template>
