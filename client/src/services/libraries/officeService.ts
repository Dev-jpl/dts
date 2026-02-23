export async function fetchOffices() {
    const response = await fetch("/data/lib_office.json");
    const data = await response.json();

    return data.map((item: any) => ({
        item_id: item.OFFICE_CODE || item.OFFICE_CODE,
        item_title: item.INFO_DIVISION,
        item_desc: `${item.SHORTNAME_REGION ?? 'OSEC'}-${item.INFO_SERVICE}`,
        return_value: {
            region: item.SHORTNAME_REGION ?? 'OSEC',
            service: item.INFO_SERVICE,
            office: item.INFO_DIVISION,
            office_code: item.OFFICE_CODE,
        },
    }));
}