<x-layouts.app>
  <h2 class="text-xl font-semibold mb-3">Audit Log</h2>

  <form class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-4">
    <input class="border p-2 rounded" type="number" name="user" value="{{ request('user') }}" placeholder="User ID">
    <select class="border p-2 rounded" name="event">
      <option value="">Event</option>
      @foreach(['created','updated','deleted','restored'] as $e)
        <option value="{{ $e }}" @selected(request('event')==$e)>{{ ucfirst($e) }}</option>
      @endforeach
    </select>
    <select class="border p-2 rounded" name="type">
      <option value="">Model</option>
      @foreach($types as $t)
        <option value="{{ $t }}" @selected(request('type')==$t)>{{ $t }}</option>
      @endforeach
    </select>
    <button class="px-3 py-2 bg-gray-800 text-white rounded">Filter</button>
  </form>

  <table class="display w-full text-sm">
    <thead>
      <tr><th>Waktu</th><th>User</th><th>Event</th><th>Model</th><th>ID</th></tr>
    </thead>
    <tbody>
      @foreach($audits as $a)
        <tr>
          <td>{{ $a->created_at }}</td>
          <td>{{ $a->user_id ?? '-' }}</td>
          <td>{{ $a->event }}</td>
          <td>{{ class_basename($a->auditable_type) }}</td>
          <td>{{ $a->auditable_id }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="mt-4">{{ $audits->links() }}</div>
  <script> $(function(){ $('table.display').DataTable({ pageLength: 25 }); }); </script>
</x-layouts.app>