export const roleTitle = (roleName) => {
    if (!roleName) return "";
    return roleName
        .replace(/[_-]/g, " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());
};
