{{-- Per-row snapshot delete. Deletes backup data, unlike the run delete on the
     job page, which only drops the record. Expects $run. --}}
@php
    $repoName = $run->job?->repository?->name;
    $where = $repoName ? ' from "' . $repoName . '"' : '';
@endphp
<x-delete-button :name="'delsnap-' . $run->id" :action="route('snapshots.destroy', $run)"
    title="Delete This Snapshot?"
    :message="'Deletes this restore point' . $where . '. The backup data is removed from the repository and cannot be restored. The run record stays as history.'"
    confirm="Delete Snapshot" label="Delete Snapshot" />
