<script setup>
import { ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
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
    canRequestRevision: Boolean,
    canDeliver: Boolean,
    canCancel: Boolean,
    revisions: Array,
    canSubmitWork: Boolean,
    maxUploadMb: Number,
    allowedOutputExtensions: String,
    timeline: Array,
    enableDesignerTips: Boolean,
    currency: String,
    commissions: Array,
    comments: Array,
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

const showRevisionModal = ref(false);
const revisionNotes = ref("");
const requestingRevision = ref(false);

const showDeliverModal = ref(false);
const deliverMessage = ref("");
const selectedFileIds = ref([]);
const delivering = ref(false);
const designerTip = ref("");

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

const submitRevision = () => {
    requestingRevision.value = true;
    router.post(
        route("orders.request-revision", props.order.id),
        {
            notes: revisionNotes.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showRevisionModal.value = false;
                revisionNotes.value = "";
            },
            onFinish: () => {
                requestingRevision.value = false;
            },
        }
    );
};

const submitDeliver = () => {
    delivering.value = true;

    const data = {
        message: deliverMessage.value,
        file_ids: selectedFileIds.value,
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

    router.post(route("orders.submit-work", props.order.id), formData, {
        preserveScroll: true,
        onSuccess: () => {
            submitFiles.value = [];
            submitNotes.value = "";
            if (fileInput.value) {
                fileInput.value.value = "";
            }
        },
        onFinish: () => {
            submitting.value = false;
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
        secondary: "bg-gray-600 hover:bg-gray-700 text-white",
    };
    return classes[style] || classes.secondary;
};

const statusBadgeClass = (status) => {
    const map = {
        received: "bg-gray-100 text-gray-700",
        assigned: "bg-blue-100 text-blue-700",
        in_progress: "bg-indigo-100 text-indigo-700",
        submitted: "bg-purple-100 text-purple-700",
        in_review: "bg-cyan-100 text-cyan-700",
        revision_requested: "bg-yellow-100 text-yellow-800",
        approved: "bg-green-100 text-green-700",
        delivered: "bg-emerald-100 text-emerald-700",
        closed: "bg-gray-100 text-gray-600",
        cancelled: "bg-red-100 text-red-700",
    };
    return map[status] || "bg-gray-100 text-gray-700";
};

const priorityBadgeClass = (priority) => {
    return priority === "rush"
        ? "bg-red-50 text-red-700 ring-1 ring-red-200"
        : "bg-gray-50 text-gray-600 ring-1 ring-gray-200";
};
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
                            <h2 class="text-lg font-semibold text-gray-900">
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
                        <p class="mt-0.5 text-sm text-gray-500">
                            {{ order.title }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('orders.edit', order.id)"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('orders.index')"
                        class="inline-flex items-center rounded-md bg-gray-900 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-gray-800"
                    >
                        Back
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main Content (Left) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Order Details -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Order Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3"
                                >
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Type
                                        </dt>
                                        <dd
                                            class="mt-1 text-sm text-gray-900 capitalize"
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
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Client
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.client.name }}
                                        </dd>
                                        <dd class="text-xs text-gray-500">
                                            {{ order.client.email }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Designer
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{
                                                order.designer?.name ??
                                                "Unassigned"
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Sales
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{
                                                order.sales?.name ??
                                                "Unassigned"
                                            }}
                                        </dd>
                                    </div>
                                    <div v-if="order.po_number">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            PO #
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.po_number }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Price
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{
                                                order.price_amount
                                                    ? `${order.currency} ${order.price_amount}`
                                                    : "—"
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Due Date
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{
                                                order.due_at
                                                    ? formatDate(order.due_at)
                                                    : "—"
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Created
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{
                                                formatDate(
                                                    order.created_at,
                                                    true
                                                )
                                            }}
                                        </dd>
                                    </div>
                                </dl>
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
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Digitizing Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4"
                                >
                                    <div v-if="order.height">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Height
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.height }}"
                                        </dd>
                                    </div>
                                    <div v-if="order.width">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Width
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.width }}"
                                        </dd>
                                    </div>
                                    <div v-if="order.placement">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Placement
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.placement }}
                                        </dd>
                                    </div>
                                    <div v-if="order.file_format">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            File Format
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
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
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Patch Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3"
                                >
                                    <div v-if="order.patch_type">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Patch Type
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.patch_type }}
                                        </dd>
                                    </div>
                                    <div v-if="order.quantity">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Quantity
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.quantity }}
                                        </dd>
                                    </div>
                                    <div v-if="order.height">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Size
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.height }}" x
                                            {{ order.width }}"
                                        </dd>
                                    </div>
                                    <div v-if="order.backing">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Backing
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.backing }}
                                        </dd>
                                    </div>
                                    <div v-if="order.placement">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Placement
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.placement }}
                                        </dd>
                                    </div>
                                    <div v-if="order.merrow_border">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Merrow Border
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.merrow_border }}
                                        </dd>
                                    </div>
                                    <div v-if="order.num_colors">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Colors
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.num_colors }}
                                        </dd>
                                    </div>
                                    <div v-if="order.fabric">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Fabric
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.fabric }}
                                        </dd>
                                    </div>
                                    <div v-if="order.need_by">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Need By
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ formatDate(order.need_by) }}
                                        </dd>
                                    </div>
                                </dl>
                                <div
                                    v-if="order.shipping_address"
                                    class="mt-4 pt-4 border-t border-gray-100"
                                >
                                    <dt
                                        class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                    >
                                        Shipping Address
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm text-gray-900 whitespace-pre-line"
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
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Vector Details
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <dl
                                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-4"
                                >
                                    <div v-if="order.color_type">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Color Type
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.color_type }}
                                        </dd>
                                    </div>
                                    <div v-if="order.num_colors">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Colors
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.num_colors }}
                                        </dd>
                                    </div>
                                    <div v-if="order.vector_order_type">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Order Type
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.vector_order_type }}
                                        </dd>
                                    </div>
                                    <div v-if="order.required_format">
                                        <dt
                                            class="text-xs font-medium text-gray-500 uppercase tracking-wide"
                                        >
                                            Format
                                        </dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ order.required_format }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Instructions
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <p
                                    class="whitespace-pre-line text-sm text-gray-700 leading-relaxed"
                                >
                                    {{
                                        order.instructions ||
                                        "No instructions provided."
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Revision History -->
                        <div
                            v-if="revisions?.length"
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Revisions ({{ revisions.length }})
                                </h3>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div
                                    v-for="revision in revisions"
                                    :key="revision.id"
                                    class="px-5 py-3"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-sm text-gray-700 whitespace-pre-line"
                                            >
                                                {{
                                                    revision.notes ||
                                                    "No notes provided."
                                                }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-gray-500"
                                            >
                                                Requested by
                                                {{
                                                    revision.requested_by
                                                }}
                                                &bull;
                                                {{
                                                    formatDate(
                                                        revision.created_at,
                                                        true
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <span
                                            :class="[
                                                'ml-3 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                revision.status === 'resolved'
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-yellow-100 text-yellow-700',
                                            ]"
                                        >
                                            {{
                                                revision.status === "resolved"
                                                    ? "Resolved"
                                                    : "Open"
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Files -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Input Files
                                </h3>
                            </div>
                            <div v-if="inputFiles?.length">
                                <div
                                    v-for="file in inputFiles"
                                    :key="file.id"
                                    class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-medium text-gray-900 truncate"
                                        >
                                            {{ file.original_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatSize(file.size) }} &bull;
                                            {{
                                                formatDate(
                                                    file.uploaded_at,
                                                    true
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <a
                                        :href="file.download_url"
                                        class="ml-3 inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100"
                                    >
                                        Download
                                    </a>
                                </div>
                            </div>
                            <div v-else class="px-5 py-4">
                                <p class="text-sm text-gray-500">
                                    No input files uploaded.
                                </p>
                            </div>
                        </div>

                        <!-- Output Files -->
                        <div
                            v-if="outputFiles?.length"
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Output Files
                                </h3>
                            </div>
                            <div>
                                <div
                                    v-for="file in outputFiles"
                                    :key="file.id"
                                    class="flex items-center justify-between px-5 py-3 border-b border-gray-50 last:border-0"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-medium text-gray-900 truncate"
                                        >
                                            {{ file.original_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatSize(file.size) }} &bull;
                                            {{
                                                formatDate(
                                                    file.uploaded_at,
                                                    true
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <a
                                        :href="file.download_url"
                                        class="ml-3 inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200 hover:bg-gray-100"
                                    >
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Work Form -->
                        <div
                            v-if="canSubmitWork"
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Submit Work
                                </h3>
                            </div>
                            <div class="px-5 py-4 space-y-4">
                                <p class="text-xs text-gray-500">
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
                                        class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                        @change="handleFileSelect"
                                    />
                                    <p
                                        v-if="page.props.errors?.files"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ page.props.errors.files }}
                                    </p>
                                </div>

                                <div
                                    v-if="submitFiles.length"
                                    class="rounded-md border border-gray-200 divide-y divide-gray-100"
                                >
                                    <div
                                        v-for="(file, index) in submitFiles"
                                        :key="index"
                                        class="flex items-center justify-between px-3 py-2"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-medium text-gray-900"
                                            >
                                                {{ file.name }}
                                            </p>
                                            <p class="text-xs text-gray-500">
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

                                <div>
                                    <label
                                        for="submit_notes"
                                        class="block text-xs font-medium text-gray-700"
                                        >Notes (optional)</label
                                    >
                                    <textarea
                                        v-model="submitNotes"
                                        id="submit_notes"
                                        rows="2"
                                        placeholder="Any notes about your submission..."
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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
                                            : "Submit Work"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar (Right) -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <div
                            v-if="
                                allowedTransitions?.length ||
                                canRequestRevision ||
                                canDeliver ||
                                canCancel
                            "
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
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
                                        @click="
                                            showDeliverModal = true;
                                            selectAllFiles();
                                        "
                                    >
                                        Deliver Order
                                    </button>
                                    <button
                                        v-if="canRequestRevision"
                                        type="button"
                                        class="inline-flex items-center rounded-md px-3 py-1.5 text-xs font-medium shadow-sm bg-yellow-500 hover:bg-yellow-600 text-white"
                                        @click="showRevisionModal = true"
                                    >
                                        Request Revision
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
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Assign Designer
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <div class="space-y-3">
                                    <select
                                        v-model="selectedDesigner"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Assign Sales
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <div class="space-y-3">
                                    <select
                                        v-model="selectedSales"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Commissions & Earnings
                                </h3>
                            </div>
                            <div class="divide-y divide-gray-50">
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
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{
                                                        commission.user?.name ??
                                                        "Unknown"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="mt-1 text-xs text-gray-500"
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
                                                class="mt-2 text-xs text-gray-600 bg-gray-50 rounded px-2 py-1"
                                            >
                                                {{ commission.notes }}
                                            </div>
                                        </div>
                                        <div
                                            class="flex flex-col items-end gap-1 ml-4"
                                        >
                                            <div class="text-right">
                                                <div
                                                    class="text-sm font-semibold text-gray-900"
                                                >
                                                    <!-- {{ commission.currency }} {{ commission.total_amount.toFixed(2) }} -->
                                                </div>
                                                <div
                                                    v-if="
                                                        commission.extra_amount >
                                                        0
                                                    "
                                                    class="text-xs text-gray-500"
                                                >
                                                    Base:
                                                    {{ commission.currency }}
                                                    {{
                                                        commission.base_amount.toFixed(
                                                            2
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    v-if="
                                                        commission.extra_amount >
                                                        0
                                                    "
                                                    class="text-xs text-indigo-600 font-medium"
                                                >
                                                    + Tip:
                                                    {{ commission.currency }}
                                                    {{
                                                        commission.extra_amount.toFixed(
                                                            2
                                                        )
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

                        <!-- Activity Timeline -->
                        <div
                            v-if="timeline?.length"
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Activity
                                </h3>
                            </div>
                            <div class="px-5 py-4">
                                <OrderTimeline :events="timeline" />
                            </div>
                        </div>

                        <!-- Comments -->
                        <div
                            class="bg-white shadow-sm rounded-lg border border-gray-200"
                        >
                            <div class="px-5 py-4 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-900">
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
                                        class="border-l-2 pl-4"
                                        :class="
                                            comment.visibility === 'internal'
                                                ? 'border-yellow-300 bg-yellow-50/30'
                                                : 'border-indigo-200'
                                        "
                                    >
                                        <div
                                            class="flex items-start justify-between"
                                        >
                                            <div>
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <p
                                                        class="text-sm font-medium text-gray-900"
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
                                                    class="text-xs text-gray-500"
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
                                            class="mt-2 text-sm text-gray-700 whitespace-pre-wrap"
                                        >
                                            {{ comment.body }}
                                        </p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-gray-500">
                                    No comments yet.
                                </p>

                                <!-- Add Comment Form -->
                                <div class="pt-4 border-t border-gray-200">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                        >Add a comment</label
                                    >
                                    <textarea
                                        v-model="newComment"
                                        rows="3"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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
                                                    class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
                                                    >Visible to client</span
                                                >
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    v-model="commentVisibility"
                                                    type="radio"
                                                    value="internal"
                                                    class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span
                                                    class="ml-2 text-sm text-gray-700"
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
                    class="fixed inset-0 bg-gray-500/75"
                    @click="showDeliverModal = false"
                ></div>
                <div
                    class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        Deliver Order
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Send the completed work to the client via email.
                    </p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Message to Client (optional)</label
                            >
                            <textarea
                                v-model="deliverMessage"
                                rows="3"
                                placeholder="Add a message or feedback for the client..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
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

                        <div v-if="outputFiles?.length">
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Attach Files</label
                            >
                            <p class="text-xs text-gray-500 mt-0.5">
                                Select which files to send as email attachments.
                            </p>
                            <div
                                class="mt-2 max-h-48 overflow-y-auto rounded-md border border-gray-200 divide-y divide-gray-100"
                            >
                                <label
                                    v-for="file in outputFiles"
                                    :key="file.id"
                                    class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="
                                            selectedFileIds.includes(file.id)
                                        "
                                        @change="toggleFileSelection(file.id)"
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-medium text-gray-900 truncate"
                                        >
                                            {{ file.original_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatSize(file.size) }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
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
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
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

        <!-- Revision Request Modal -->
        <div
            v-if="showRevisionModal"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div class="flex min-h-full items-center justify-center p-4">
                <div
                    class="fixed inset-0 bg-gray-500/75"
                    @click="showRevisionModal = false"
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        Request Revision
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Provide feedback on what needs to be changed.
                    </p>
                    <div class="mt-4">
                        <textarea
                            v-model="revisionNotes"
                            rows="4"
                            placeholder="Describe what changes are needed..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        ></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="showRevisionModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="requestingRevision"
                            class="rounded-md bg-yellow-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-yellow-600 disabled:opacity-50"
                            @click="submitRevision"
                        >
                            {{
                                requestingRevision
                                    ? "Requesting..."
                                    : "Request Revision"
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
                    class="fixed inset-0 bg-gray-500/75"
                    @click="showCancelModal = false"
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        Cancel Order
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        This action cannot be undone. Please provide a reason.
                    </p>
                    <div class="mt-4">
                        <textarea
                            v-model="cancelReason"
                            rows="3"
                            placeholder="Reason for cancellation..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        ></textarea>
                    </div>
                    <div class="mt-4 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
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
                    class="fixed inset-0 bg-gray-500/75"
                    @click="
                        showStatusModal = false;
                        pendingStatus = null;
                        pendingTransition = null;
                    "
                ></div>
                <div
                    class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        Confirm Status Change
                    </h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Are you sure you want to change the order status to
                        <span class="font-semibold">{{
                            pendingTransition?.label
                        }}</span
                        >?
                    </p>
                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="
                                showStatusModal = false;
                                pendingStatus = null;
                                pendingTransition = null;
                            "
                        >
                            Cancel
                        </button>
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
                    class="fixed inset-0 bg-gray-500/75"
                    @click="showCommissionTipModal = false"
                ></div>
                <div
                    class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{
                            selectedCommission?.extra_amount > 0
                                ? "Edit"
                                : "Add"
                        }}
                        Commission Tip
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Add an extra amount (tip/bonus) for
                        {{ selectedCommission?.user?.name }}.
                    </p>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Tip Amount *</label
                            >
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <span class="text-gray-500 sm:text-sm">{{
                                        currency
                                    }}</span>
                                </div>
                                <input
                                    v-model="commissionTipAmount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="block w-full pl-12 pr-3 py-2 border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="0.00"
                                />
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Current base: {{ currency }}
                                {{
                                    selectedCommission?.base_amount?.toFixed(2)
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Notes (Optional)</label
                            >
                            <textarea
                                v-model="commissionTipNotes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Add a note about this tip..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            @click="showCommissionTipModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="!commissionTipAmount || updatingTip"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            @click="submitCommissionTip"
                        >
                            {{ updatingTip ? "Updating..." : "Update Tip" }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
