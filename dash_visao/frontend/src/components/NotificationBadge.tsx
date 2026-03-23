import React from 'react';

interface NotificationBadgeProps {
  count: number;
}

const NotificationBadge: React.FC<NotificationBadgeProps> = ({ count }) => {
  if (count === 0) {
    return null;
  }

  return (
    <div className="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 bg-system-wine text-white text-xs font-bold rounded-full">
      {count > 9 ? '9+' : count}
    </div>
  );
};

export default NotificationBadge; 